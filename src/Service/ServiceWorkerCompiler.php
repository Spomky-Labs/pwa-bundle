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
use const PHP_EOL;

final readonly class ServiceWorkerCompiler
{
    private array $jsonOptions;

    private string $manifestPublicUrl;

    public function __construct(
        private SerializerInterface $serializer,
        #[Autowire('%spomky_labs_pwa.manifest.public_url%')]
        string $manifestPublicUrl,
        #[Autowire('%spomky_labs_pwa.sw.enabled%')]
        private bool $serviceWorkerEnabled,
        #[Autowire('%spomky_labs_pwa.asset_public_prefix%')]
        private string $assetPublicPrefix,
        private Manifest $manifest,
        private ServiceWorker $serviceWorker,
        private AssetMapperInterface $assetMapper,
        #[Autowire('%kernel.debug%')]
        bool $debug,
    ) {
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
        $options = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ];
        if ($debug === true) {
            $options[JsonEncode::OPTIONS] |= JSON_PRETTY_PRINT;
        }
        $this->jsonOptions = $options;
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
        $body = $this->processWidgets($body);

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

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processWorkbox(Workbox $workbox, string $body): string
    {
        $body = $this->processWorkboxImport($workbox, $body);
        $body = $this->processClearCache($workbox, $body);
        $body = $this->processAssetCacheRules($workbox, $body);
        $body = $this->processFontCacheRules($workbox, $body);
        $body = $this->processPageImageCacheRule($workbox, $body);
        $body = $this->processImageCacheRule($workbox, $body);
        $body = $this->processCacheRootFilesRule($workbox, $body);
        $body = $this->processCacheGoogleFontsRule($workbox, $body);
        $body = $this->processBackgroundSyncRule($workbox, $body);

        return $this->processOfflineFallback($workbox, $body);
    }

    private function processWorkboxImport(Workbox $workbox, string $body): string
    {
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

        return trim($declaration) . PHP_EOL . PHP_EOL . $body;
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

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processAssetCacheRules(Workbox $workbox, string $body): string
    {
        if ($workbox->assetCache->enabled === false) {
            return $body;
        }
        $assets = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            if (preg_match($workbox->assetCache->regex, $asset->sourcePath) === 1) {
                $assets[] = $asset->publicPath;
            }
        }
        $assetUrls = $this->serializer->serialize($assets, 'json', $this->jsonOptions);
        $assetUrlsLength = count($assets) * 2;

        $declaration = <<<ASSET_CACHE_RULE_STRATEGY
const assetCacheStrategy = new workbox.strategies.CacheFirst({
  cacheName: '{$workbox->assetCache->cacheName}',
  plugins: [
    new workbox.cacheableResponse.CacheableResponsePlugin({statuses: [0, 200]}),
    new workbox.expiration.ExpirationPlugin({
      maxEntries: {$assetUrlsLength},
      maxAgeSeconds: 365 * 24 * 60 * 60,
    }),
  ],
});
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
ASSET_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processFontCacheRules(Workbox $workbox, string $body): string
    {
        if ($workbox->fontCache->enabled === false) {
            return $body;
        }
        $fonts = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            if (preg_match($workbox->fontCache->regex, $asset->sourcePath) === 1) {
                $fonts[] = $asset->publicPath;
            }
        }
        $fontUrls = $this->serializer->serialize($fonts, 'json', $this->jsonOptions);

        $declaration = <<<FONT_CACHE_RULE_STRATEGY
const fontCacheStrategy = new workbox.strategies.CacheFirst({
  cacheName: '{$workbox->fontCache->cacheName}',
  plugins: [
    new workbox.cacheableResponse.CacheableResponsePlugin({
      statuses: [0, 200],
    }),
    new workbox.expiration.ExpirationPlugin({
      maxAgeSeconds: {$workbox->fontCache->maxAge},
      maxEntries: {$workbox->fontCache->maxEntries},
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
FONT_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processPageImageCacheRule(Workbox $workbox, string $body): string
    {
        if ($workbox->pageCache->enabled === false) {
            return $body;
        }
        $routes = $this->serializer->serialize($workbox->pageCache->urls, 'json', $this->jsonOptions);

        $declaration = <<<PAGE_CACHE_RULE_STRATEGY
workbox.recipes.pageCache({
    cacheName: '{$workbox->pageCache->cacheName}',
    networkTimeoutSeconds: {$workbox->pageCache->networkTimeout},
    warmCache: {$routes}
});
PAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processImageCacheRule(Workbox $workbox, string $body): string
    {
        if ($workbox->imageCache->enabled === false) {
            return $body;
        }
        $declaration = <<<IMAGE_CACHE_RULE_STRATEGY
workbox.routing.registerRoute(
  ({request, url}) => (request.destination === 'image' && !url.pathname.startsWith('{$this->assetPublicPrefix}')),
  new workbox.strategies.CacheFirst({
    cacheName: '{$workbox->imageCache->cacheName}',
    plugins: [
      new workbox.cacheableResponse.CacheableResponsePlugin({statuses: [0, 200]}),
      new workbox.expiration.ExpirationPlugin({
        maxEntries: {$workbox->imageCache->maxEntries},
        maxAgeSeconds: {$workbox->imageCache->maxAge},
      }),
    ],
  })
);
IMAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processCacheRootFilesRule(Workbox $workbox, string $body): string
    {
        if ($workbox->cacheManifest === false) {
            return $body;
        }

        $declaration = <<<IMAGE_CACHE_RULE_STRATEGY
workbox.routing.registerRoute(
  ({url}) => '{$this->manifestPublicUrl}' === url.pathname,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'manifest'
  })
);
IMAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processCacheGoogleFontsRule(Workbox $workbox, string $body): string
    {
        if ($workbox->googleFontCache->enabled === false) {
            return $body;
        }
        $options = [
            'cachePrefix' => $workbox->googleFontCache->cachePrefix,
            'maxAge' => $workbox->googleFontCache->maxAge,
            'maxEntries' => $workbox->googleFontCache->maxEntries,
        ];
        $options = array_filter($options, static fn (mixed $v): bool => ($v !== null && $v !== ''));
        $options = count($options) === 0 ? '' : $this->serializer->serialize($options, 'json', $this->jsonOptions);

        $declaration = <<<IMAGE_CACHE_RULE_STRATEGY
workbox.recipes.googleFontsCache({$options});
IMAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processBackgroundSyncRule(Workbox $workbox, string $body): string
    {
        if ($workbox->backgroundSync === []) {
            return $body;
        }

        $declaration = '';
        foreach ($workbox->backgroundSync as $sync) {
            $options = [
                'maxRetentionTime' => $sync->maxRetentionTime,
                'forceSyncCallback' => $sync->forceSyncFallback,
            ];
            $options = array_filter($options, static fn (mixed $v): bool => $v !== null);
            $options = count($options) === 0 ? '' : $this->serializer->serialize($options, 'json', $this->jsonOptions);
            $declaration .= <<<BACKGROUND_SYNC_RULE_STRATEGY
workbox.routing.registerRoute(
    '{$sync->regex}',
    new workbox.strategies.NetworkOnly({plugins: [new workbox.backgroundSync.BackgroundSyncPlugin('{$sync->queueName}',{$options})] }),
    '{$sync->method}'
);
BACKGROUND_SYNC_RULE_STRATEGY;
        }

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processOfflineFallback(Workbox $workbox, string $body): string
    {
        if ($workbox->offlineFallback->enabled === false) {
            return $body;
        }
        $options = [
            'pageFallback' => $workbox->offlineFallback->pageFallback,
            'imageFallback' => $workbox->offlineFallback->imageFallback,
            'fontFallback' => $workbox->offlineFallback->fontFallback,
        ];
        $options = array_filter($options, static fn (mixed $v): bool => $v !== null);
        $options = count($options) === 0 ? '' : $this->serializer->serialize($options, 'json', $this->jsonOptions);

        $declaration = <<<OFFLINE_FALLBACK_STRATEGY
workbox.routing.setDefaultHandler(new workbox.strategies.NetworkOnly());
workbox.recipes.offlineFallback({$options});
OFFLINE_FALLBACK_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    private function processWidgets(string $body): string
    {
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

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }
}
