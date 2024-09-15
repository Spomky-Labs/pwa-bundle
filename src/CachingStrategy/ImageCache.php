<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use Symfony\Component\AssetMapper\Path\PublicAssetsPathResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function sprintf;

final class ImageCache implements HasCacheStrategiesInterface, CanLogInterface
{
    private readonly string $assetPublicPrefix;

    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    public function __construct(
        ServiceWorker $serviceWorker,
        #[Autowire(service: 'asset_mapper.public_assets_path_resolver')]
        PublicAssetsPathResolverInterface $publicAssetsPathResolver,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->assetPublicPrefix = rtrim($publicAssetsPathResolver->resolvePublicPath(''), '/');
        $this->logger = new NullLogger();
    }

    public function getCacheStrategies(): array
    {
        $strategies = [
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
        $this->logger->debug('Image cache strategies', [
            'strategies' => $strategies,
        ]);

        return $strategies;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
