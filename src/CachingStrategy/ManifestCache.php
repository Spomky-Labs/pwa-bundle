<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ManifestCache implements HasCacheStrategiesInterface
{
    private string $manifestPublicUrl;

    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        #[Autowire('%spomky_labs_pwa.manifest.public_url%')]
        string $manifestPublicUrl,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
    }

    public function getCacheStrategies(): array
    {
        return [
            WorkboxCacheStrategy::create(
                $this->workbox->enabled && $this->workbox->cacheManifest,
                true,
                CacheStrategyInterface::STRATEGY_STALE_WHILE_REVALIDATE,
                sprintf("({url}) => '%s' === url.pathname", $this->manifestPublicUrl),
            )
                ->withName('manifest'),
        ];
    }
}
