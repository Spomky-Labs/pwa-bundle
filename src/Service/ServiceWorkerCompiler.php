<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use function assert;
use function count;
use function is_string;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class ServiceWorkerCompiler
{
    public function __construct(
        private SerializerInterface $serializer,
        #[Autowire('%spomky_labs_pwa.sw.enabled%')]
        private bool $serviceWorkerEnabled,
        private Manifest $manifest,
        private ServiceWorker $serviceWorker,
        private AssetMapperInterface $assetMapper,
    ) {
    }

    public function compile(): ?string
    {
        if ($this->serviceWorkerEnabled === false) {
            return null;
        }
        $serviceWorker = $this->serviceWorker;
        if ($serviceWorker === null) {
            return null;
        }

        if (! str_starts_with($serviceWorker->src, '/')) {
            $asset = $this->assetMapper->getAsset($serviceWorker->src);
            assert($asset !== null, 'Unable to find service worker source asset');
            $body = $asset->content ?? file_get_contents($asset->sourcePath);
        } else {
            $body = file_get_contents($serviceWorker->src);
        }
        assert(is_string($body), 'Unable to find service worker source content');
        $workbox = $serviceWorker->workbox;
        if ($workbox->enabled === true) {
            $body = $this->processWorkbox($workbox, $body);
        }

        return $body;
    }

    private function processWorkbox(Workbox $workbox, string $body): string
    {
        $body = $this->processWorkboxImport($workbox, $body);
        $body = $this->processStandardRules($workbox, $body);
        $body = $this->processPrecachedAssets($workbox, $body);
        $body = $this->processWarmCacheUrls($workbox, $body);
        $body = $this->processWidgets($workbox, $body);

        return $this->processOfflineFallback($workbox, $body);
    }

    private function processStandardRules(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->standardRulesPlaceholder)) {
            return $body;
        }

        $declaration = <<<STANDARD_RULE_STRATEGY
workbox.recipes.pageCache();
workbox.recipes.imageCache();
workbox.recipes.googleFontsCache();
const matchCallback = ({request}) => request.destination === 'style' || request.destination === 'script' || request.destination === 'worker';
workbox.routing.registerRoute(
    matchCallback,
    new workbox.strategies.CacheFirst({
        cacheName: 'static-resources',
        plugins: [
            new workbox.cacheableResponse.CacheableResponsePlugin({
                statuses: [0, 200],
            }),
        ],
    })
);
STANDARD_RULE_STRATEGY;

        return str_replace($workbox->standardRulesPlaceholder, trim($declaration), $body);
    }

    private function processPrecachedAssets(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->precachingPlaceholder)) {
            return $body;
        }
        $result = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            $result[] = [
                'url' => $asset->publicPath,
                'revision' => $asset->digest,
            ];
        }
        $assets = $this->serializer->serialize($result, 'json', [
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);

        $declaration = <<<PRECACHE_STRATEGY
workbox.precaching.precacheAndRoute({$assets});
PRECACHE_STRATEGY;

        return str_replace($workbox->precachingPlaceholder, trim($declaration), $body);
    }

    private function processWarmCacheUrls(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->warmCachePlaceholder)) {
            return $body;
        }
        $urls = $workbox->warmCacheUrls;
        if (count($urls) === 0) {
            return $body;
        }

        $routes = $this->serializer->serialize($urls, 'json', [
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);

        $declaration = <<<WARM_CACHE_URL_STRATEGY
workbox.recipes.warmStrategyCache({
    urls: {$routes},
    strategy: new workbox.strategies.CacheFirst()
});
WARM_CACHE_URL_STRATEGY;

        return str_replace($workbox->warmCachePlaceholder, trim($declaration), $body);
    }

    private function processOfflineFallback(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->offlineFallbackPlaceholder) || $workbox->offlineFallback === null) {
            return $body;
        }

        $url = $this->serializer->serialize($workbox->offlineFallback, 'json', [
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);

        $declaration = <<<OFFLINE_FALLBACK_STRATEGY
workbox.routing.setDefaultHandler(new workbox.strategies.NetworkOnly());
workbox.recipes.offlineFallback({ pageFallback: {$url} });
OFFLINE_FALLBACK_STRATEGY;

        return str_replace($workbox->offlineFallbackPlaceholder, trim($declaration), $body);
    }

    private function processWidgets(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->widgetsPlaceholder)) {
            return $body;
        }
        $tags = [];
        foreach ($this->manifest->widgets as $widget) {
            if ($widget->tag !== null) {
                $tags[] = $widget->tag;
            }
        }
        if (count($tags) === 0) {
            return $body;
        }
        $data = $this->serializer->serialize($tags, 'json', [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);

        $declaration = <<<OFFLINE_FALLBACK_STRATEGY
self.addEventListener("widgetinstall", event => {
    event.waitUntil(renderWidget(event.widget));
});
async function renderWidget(widget) {
    const templateUrl = widget.definition.msAcTemplate;
    const dataUrl = widget.definition.data;
    const template = await (await fetch(templateUrl)).text();
    const data = await (await fetch(dataUrl)).text();
    await self.widgets.updateByTag(widget.definition.tag, {template, data});
}

self.addEventListener("widgetinstall", event => {
    event.waitUntil(onWidgetInstall(event.widget));
});
async function onWidgetInstall(widget) {
    const tags = await self.registration.periodicSync.getTags();
    if (!tags.includes(widget.definition.tag)) {
        await self.registration.periodicSync.register(widget.definition.tag, {
            minInterval: widget.definition.update
        });
    }
    await updateWidget(widget);
}

self.addEventListener("widgetuninstall", event => {
    event.waitUntil(onWidgetUninstall(event.widget));
});

async function onWidgetUninstall(widget) {
    if (widget.instances.length === 1 && "update" in widget.definition) {
        await self.registration.periodicSync.unregister(widget.definition.tag);
    }
}
self.addEventListener("periodicsync", async event => {
    const widget = await self.widgets.getByTag(event.tag);
    if (widget && "update" in widget.definition) {
        event.waitUntil(renderWidget(widget));
    }
});

self.addEventListener("activate", event => {
    event.waitUntil(updateWidgets());
});

async function updateWidgets() {
    const tags = {$data};
    if(!self.widgets || tags.length === 0) return;
    for (const tag of tags) {
        const widget = await self.widgets.getByTag(tag);
        if (!widget) {
            continue;
        }
        const template = await (await fetch(widget.definition.msAcTemplate)).text();
        const data = await (await fetch(widget.definition.data)).text();
        await self.widgets.updateByTag(widget.definition.tag, {template, data});
    }
}
OFFLINE_FALLBACK_STRATEGY;

        return str_replace($workbox->widgetsPlaceholder, trim($declaration), $body);
    }

    private function processWorkboxImport(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->workboxImportPlaceholder)) {
            return $body;
        }
        if ($workbox->useCDN === true) {
            $declaration = <<<IMPORT_CDN_STRATEGY
importScripts(
    'https://storage.googleapis.com/workbox-cdn/releases/{$workbox->version}/workbox-sw.js'
);
IMPORT_CDN_STRATEGY;
        } else {
            $publicUrl = '/' . trim($workbox->workboxPublicUrl, '/');
            $declaration = <<<IMPORT_CDN_STRATEGY
importScripts('{$publicUrl}/workbox-sw.js');

workbox.setConfig({
  modulePathPrefix: '{$publicUrl}',
});
IMPORT_CDN_STRATEGY;
        }

        return str_replace($workbox->workboxImportPlaceholder, trim($declaration), $body);
    }
}
