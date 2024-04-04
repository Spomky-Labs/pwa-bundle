<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\AssetMapper\Path\PublicAssetsPathResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ImageCache implements HasCacheStrategiesInterface
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

    public function getCacheStrategies(): array
    {
        return [
            WorkboxCacheStrategy::create(
                $this->workbox->enabled && $this->workbox->imageCache->enabled,
                true,
                CacheStrategyInterface::STRATEGY_CACHE_FIRST,
                sprintf(
                    "({request, url}) => (request.destination === 'image' && !url.pathname.startsWith('%s'))",
                    $this->assetPublicPrefix
                )
            )
                ->withName($this->workbox->imageCache->cacheName ?? 'images'),
        ];
    }
}
