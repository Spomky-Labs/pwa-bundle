<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use Symfony\Component\AssetMapper\Path\PublicAssetsPathResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use const PHP_EOL;

final readonly class ImageCache implements ServiceWorkerRule, HasCacheStrategies
{
    private string $assetPublicPrefix;

    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        #[Autowire(service: 'asset_mapper.public_assets_path_resolver')]
        PublicAssetsPathResolverInterface $publicAssetsPathResolver,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->assetPublicPrefix = rtrim($publicAssetsPathResolver->resolvePublicPath(''), '/');
    }

    public function process(string $body): string
    {
        if ($this->workbox->enabled === false) {
            return $body;
        }
        if ($this->workbox->imageCache->enabled === false) {
            return $body;
        }
        $declaration = <<<IMAGE_CACHE_RULE_STRATEGY
workbox.routing.registerRoute(
  ({request, url}) => (request.destination === 'image' && !url.pathname.startsWith('{$this->assetPublicPrefix}')),
  new workbox.strategies.CacheFirst({
    cacheName: '{$this->workbox->imageCache->cacheName}',
    plugins: [
      new workbox.cacheableResponse.CacheableResponsePlugin({statuses: [0, 200]}),
      new workbox.expiration.ExpirationPlugin({
        maxEntries: {$this->workbox->imageCache->maxEntries},
        maxAgeSeconds: {$this->workbox->imageCache->maxAge},
      }),
    ],
  })
);
IMAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    public function getCacheStrategies(): array
    {
        return [
            CacheStrategy::create(
                $this->workbox->imageCache->cacheName,
                CacheStrategy::STRATEGY_CACHE_FIRST,
                sprintf(
                    "'({request, url}) => (request.destination === 'image' && !url.pathname.startsWith('%s'))'",
                    $this->assetPublicPrefix
                ),
                $this->workbox->enabled && $this->workbox->imageCache->enabled,
                true,
                [
                    'maxEntries' => $this->workbox->imageCache->maxEntries,
                    'maxAge' => $this->workbox->imageCache->maxAge,
                ]
            ),
        ];
    }
}
