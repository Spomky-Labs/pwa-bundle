<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use SpomkyLabs\PwaBundle\Service\Plugin\CachePlugin;
use SpomkyLabs\PwaBundle\Service\WorkboxCacheStrategy;
use Symfony\Component\AssetMapper\Path\PublicAssetsPathResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ImageCache implements HasCacheStrategies
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
                $this->workbox->imageCache->cacheName ?? 'images',
                CacheStrategy::STRATEGY_CACHE_FIRST,
                sprintf(
                    "({request, url}) => (request.destination === 'image' && !url.pathname.startsWith('%s'))",
                    $this->assetPublicPrefix
                ),
                $this->workbox->enabled && $this->workbox->imageCache->enabled,
                true,
                null,
                [
                    CachePlugin::createCacheableResponsePlugin(),
                    CachePlugin::createExpirationPlugin(
                        $this->workbox->imageCache->maxEntries ?? 60,
                        $this->workbox->imageCache->maxAgeInSeconds() ?? 60 * 60 * 24 * 7
                    ),
                ]
            ),
        ];
    }
}
