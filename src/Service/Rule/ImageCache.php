<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\AssetMapper\Path\PublicAssetsPathResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use const PHP_EOL;

final readonly class ImageCache implements WorkboxRule
{
    private string $assetPublicPrefix;

    public function __construct(
        #[Autowire(service: 'asset_mapper.public_assets_path_resolver')]
        PublicAssetsPathResolverInterface $publicAssetsPathResolver,
    ) {
        $this->assetPublicPrefix = rtrim($publicAssetsPathResolver->resolvePublicPath(''), '/');
    }

    public function process(Workbox $workbox, string $body): string
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
}
