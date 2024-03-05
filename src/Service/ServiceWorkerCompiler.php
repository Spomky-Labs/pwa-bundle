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
        #[Autowire('%spomky_labs_pwa.asset_public_prefix%')]
        private string $assetPublicPrefix,
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

        if (! str_starts_with($serviceWorker->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($serviceWorker->src->src);
            assert($asset !== null, 'Unable to find service worker source asset');
            $body = $asset->content ?? file_get_contents($asset->sourcePath);
        } else {
            $body = file_get_contents($serviceWorker->src->src);
        }
        assert(is_string($body), 'Unable to find service worker source content');
        $workbox = $serviceWorker->workbox;
        if ($workbox->enabled === true) {
            $body = $this->processWorkbox($workbox, $body);
        }

        return $this->processSkipWaiting($body);
    }

    private function processSkipWaiting(string $body): string
    {
        if ($this->serviceWorker->skipWaiting === false) {
            return $body;
        }

        $declaration = <<<SKIP_WAITING
self.addEventListener("install", function (event) {
  event.waitUntil(self.skipWaiting());
});
self.addEventListener("activate", function (event) {
  event.waitUntil(self.clients.claim());
});
SKIP_WAITING;

        return $body . trim($declaration);
    }

    private function processWorkbox(Workbox $workbox, string $body): string
    {
        $body = $this->processWorkboxImport($workbox, $body);
        $body = $this->processClearCache($workbox, $body);
        $body = $this->processStandardRules($workbox, $body);
        $body = $this->processWidgets($workbox, $body);

        return $this->processOfflineFallback($workbox, $body);
    }

    private function processWorkboxImport(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->workboxImportPlaceholder)) {
            return $body;
        }
        if ($workbox->useCDN === true) {
            $declaration = <<<IMPORT_CDN_STRATEGY
importScripts('https://storage.googleapis.com/workbox-cdn/releases/{$workbox->version}/workbox-sw.js');
IMPORT_CDN_STRATEGY;
        } else {
            $publicUrl = '/' . trim($workbox->workboxPublicUrl, '/');
            $declaration = <<<IMPORT_CDN_STRATEGY
importScripts('{$publicUrl}/workbox-sw.js');
workbox.setConfig({modulePathPrefix: '{$publicUrl}'});
IMPORT_CDN_STRATEGY;
        }

        return str_replace($workbox->workboxImportPlaceholder, trim($declaration), $body);
    }

    private function processClearCache(Workbox $workbox, string $body): string
    {
        if ($workbox->clearCache === false) {
            return $body;
        }

        $declaration = <<<CLEAR_CACHE
self.addEventListener("install", function (event) {
    event.waitUntil(caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.map(function (cacheName) {
                    return caches.delete(cacheName);
                })
            );
        })
    );
});
CLEAR_CACHE;

        return $body . trim($declaration);
    }

    private function processStandardRules(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->standardRulesPlaceholder)) {
            return $body;
        }

        $assets = [];
        $fonts = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            if (preg_match($workbox->imageRegex, $asset->sourcePath) === 1 || preg_match(
                $workbox->staticRegex,
                $asset->sourcePath
            ) === 1) {
                $assets[] = $asset->publicPath;
            } elseif (preg_match($workbox->fontRegex, $asset->sourcePath) === 1) {
                $fonts[] = $asset->publicPath;
            }
        }
        $jsonOptions = [
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ];
        $assetUrls = $this->serializer->serialize($assets, 'json', $jsonOptions);
        $fontUrls = $this->serializer->serialize($fonts, 'json', $jsonOptions);
        $assetUrlsLength = count($assets) * 2;
        $routes = $this->serializer->serialize($workbox->warmCacheUrls, 'json', $jsonOptions);

        $declaration = <<<STANDARD_RULE_STRATEGY
// Pages cached during the navigation.
workbox.recipes.pageCache({
    cacheName: '{$workbox->pageCacheName}',
    networkTimeoutSeconds: {$workbox->networkTimeoutSeconds},
    warmCache: {$routes}
});

//Images cache
workbox.routing.registerRoute(
  ({request, url}) => (request.destination === 'image' && !url.pathname.startsWith('{$this->assetPublicPrefix}')),
  new workbox.strategies.CacheFirst({
    cacheName: '{$workbox->imageCacheName}',
    plugins: [
      new workbox.cacheableResponse.CacheableResponsePlugin({statuses: [0, 200]}),
      new workbox.expiration.ExpirationPlugin({
        maxEntries: {$workbox->maxImageCacheEntries},
        maxAgeSeconds: {$workbox->maxImageAge},
      }),
    ],
  })
);

// Assets served by Asset Mapper
// - Strategy: CacheFirst
const assetCacheStrategy = new workbox.strategies.CacheFirst({
  cacheName: '{$workbox->assetCacheName}',
  plugins: [
    new workbox.cacheableResponse.CacheableResponsePlugin({statuses: [0, 200]}),
    new workbox.expiration.ExpirationPlugin({
      maxEntries: {$assetUrlsLength},
      maxAgeSeconds: 365 * 24 * 60 * 60,
    }),
  ],
});
// - Strategy: only the Asset Mapper public route
workbox.routing.registerRoute(
  ({url}) => url.pathname.startsWith('{$this->assetPublicPrefix}'),
  assetCacheStrategy
);
self.addEventListener('install', event => {
  const done = {$assetUrls}.map(
    path =>
      assetCacheStrategy.handleAll({
        event,
        request: new Request(path),
      })[1]
  );

  event.waitUntil(Promise.all(done));
});


const fontCacheStrategy = new workbox.strategies.CacheFirst({
  cacheName: '{$workbox->fontCacheName}',
  plugins: [
    new workbox.cacheableResponse.CacheableResponsePlugin({
      statuses: [0, 200],
    }),
    new workbox.expiration.ExpirationPlugin({
      maxAgeSeconds: {$workbox->maxFontAge},
      maxEntries: {$workbox->maxFontCacheEntries},
    }),
  ],
});
workbox.routing.registerRoute(
  ({request}) => request.destination === 'font',
  fontCacheStrategy
);
self.addEventListener('install', event => {
  const done = {$fontUrls}.map(
    path =>
      fontCacheStrategy.handleAll({
        event,
        request: new Request(path),
      })[1]
  );

  event.waitUntil(Promise.all(done));
});


STANDARD_RULE_STRATEGY;

        return str_replace($workbox->standardRulesPlaceholder, trim($declaration), $body);
    }

    private function processOfflineFallback(Workbox $workbox, string $body): string
    {
        if (! str_contains($body, $workbox->offlineFallbackPlaceholder)) {
            return $body;
        }
        if ($workbox->pageFallback === null && $workbox->imageFallback === null && $workbox->fontFallback === null) {
            return $body;
        }

        $jsonOptions = [
            JsonEncode::OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ];
        $pageFallback = $workbox->pageFallback === null ? 'null' : $this->serializer->serialize(
            $workbox->pageFallback,
            'json',
            $jsonOptions
        );
        $imageFallback = $workbox->imageFallback === null ? 'null' : $this->serializer->serialize(
            $workbox->imageFallback,
            'json',
            $jsonOptions
        );
        $fontFallback = $workbox->fontFallback === null ? 'null' : $this->serializer->serialize(
            $workbox->fontFallback,
            'json',
            $jsonOptions
        );

        $declaration = <<<OFFLINE_FALLBACK_STRATEGY
workbox.routing.setDefaultHandler(new workbox.strategies.NetworkOnly());
workbox.recipes.offlineFallback({
    pageFallback: {$pageFallback},
    imageFallback: {$imageFallback},
    fontFallback: {$fontFallback}
});
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
}
