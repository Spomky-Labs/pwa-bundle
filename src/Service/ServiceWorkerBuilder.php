<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use function assert;
use function count;
use function is_string;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class ServiceWorkerBuilder
{
    private ?string $serviceWorkerPublicUrl;

    public function __construct(
        private SerializerInterface $serializer,
        private Manifest $manifest,
        private AssetMapperInterface $assetMapper,
    ) {
        $serviceWorkerPublicUrl = $manifest->serviceWorker?->dest;
        $this->serviceWorkerPublicUrl = $serviceWorkerPublicUrl === null ? null : '/' . trim(
            $serviceWorkerPublicUrl,
            '/'
        );
    }

    public function build(): ?string
    {
        if ($this->serviceWorkerPublicUrl === null) {
            return null;
        }
        $serviceWorkerSource = $this->manifest->serviceWorker?->src;
        if ($serviceWorkerSource === null) {
            return null;
        }

        if (! str_starts_with($serviceWorkerSource, '/')) {
            $asset = $this->assetMapper->getAsset($serviceWorkerSource);
            assert($asset !== null, 'Unable to find service worker source asset');
            $body = $asset->content ?? file_get_contents($asset->sourcePath);
        } else {
            $body = file_get_contents($serviceWorkerSource);
        }
        assert(is_string($body), 'Unable to find service worker source content');
        $body = $this->processPrecachedAssets($body);
        $body = $this->processWarmCacheUrls($body);
        $body = $this->processWidgets($body);

        return $this->processOfflineFallback($body);
    }

    private function processPrecachedAssets(string $body): string
    {
        $config = $this->manifest->serviceWorker;
        if ($config === null) {
            return $body;
        }
        if (! str_contains($body, $config->precachingPlaceholder)) {
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
        $precacheAndRouteDeclaration = str_contains(
            $body,
            'precacheAndRoute'
        ) ? '' : 'const { precacheAndRoute } = workbox.precaching;';

        $declaration = <<<PRECACHE_STRATEGY
{$precacheAndRouteDeclaration}
precacheAndRoute({$assets});
PRECACHE_STRATEGY;

        return str_replace($config->precachingPlaceholder, trim($declaration), $body);
    }

    private function processWarmCacheUrls(string $body): string
    {
        $config = $this->manifest->serviceWorker;
        if ($config === null) {
            return $body;
        }
        if (! str_contains($body, $config->warmCachePlaceholder)) {
            return $body;
        }
        $urls = $config->warmCacheUrls;
        if (count($urls) === 0) {
            return $body;
        }

        $routes = $this->serializer->serialize($urls, 'json', [
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);

        $cacheFirstStrategyDeclaration = str_contains(
            $body,
            'CacheFirst'
        ) ? '' : 'const { CacheFirst } = workbox.strategies;';
        $warmStrategyCacheMethod = str_contains(
            $body,
            'warmStrategyCache'
        ) ? '' : 'const { warmStrategyCache } = workbox.recipes;';

        $declaration = <<<WARM_CACHE_URL_STRATEGY
{$cacheFirstStrategyDeclaration}
{$warmStrategyCacheMethod}
warmStrategyCache({
    urls: {$routes},
    strategy: new CacheFirst()
});
WARM_CACHE_URL_STRATEGY;

        return str_replace($config->warmCachePlaceholder, trim($declaration), $body);
    }

    private function processOfflineFallback(string $body): string
    {
        $config = $this->manifest->serviceWorker;
        if ($config === null) {
            return $body;
        }
        if (! str_contains($body, $config->offlineFallbackPlaceholder) || $config->offlineFallback === null) {
            return $body;
        }

        $url = $this->serializer->serialize($config->offlineFallback, 'json', [
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);

        $offlineFallbackMethod = str_contains(
            $body,
            'offlineFallback'
        ) ? '' : 'const { offlineFallback } = workbox.recipes;';
        $networkOnlyStrategy = str_contains(
            $body,
            'NetworkOnly'
        ) ? '' : 'const { NetworkOnly } = workbox.strategies;';
        $setDefaultHandlerRouting = str_contains(
            $body,
            'setDefaultHandler'
        ) ? '' : 'const { setDefaultHandler } = workbox.routing;' . PHP_EOL . 'setDefaultHandler(new NetworkOnly());';

        $declaration = <<<OFFLINE_FALLBACK_STRATEGY
{$offlineFallbackMethod}
{$networkOnlyStrategy}
{$setDefaultHandlerRouting}
offlineFallback({
    pageFallback: {$url},
});
OFFLINE_FALLBACK_STRATEGY;

        return str_replace($config->offlineFallbackPlaceholder, trim($declaration), $body);
    }

    private function processWidgets(string $body): string
    {
        $config = $this->manifest->serviceWorker;
        if ($config === null) {
            return $body;
        }
        if (! str_contains($body, $config->widgetsPlaceholder)) {
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

        return str_replace($config->widgetsPlaceholder, trim($declaration), $body);
    }
}
