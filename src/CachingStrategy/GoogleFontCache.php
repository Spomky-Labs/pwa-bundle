<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\WorkboxPlugin\CacheableResponsePlugin;
use SpomkyLabs\PwaBundle\WorkboxPlugin\ExpirationPlugin;

final readonly class GoogleFontCache implements HasCacheStrategiesInterface
{
    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
    ) {
        $this->workbox = $serviceWorker->workbox;
    }

    public function getCacheStrategies(): array
    {
        $prefix = $this->workbox->googleFontCache->cachePrefix ?? '';
        if ($prefix !== '') {
            $prefix .= '-';
        }

        return [
            WorkboxCacheStrategy::create(
                $this->workbox->enabled && $this->workbox->googleFontCache->enabled,
                true,
                CacheStrategyInterface::STRATEGY_STALE_WHILE_REVALIDATE,
                "({url}) => url.origin === 'https://fonts.googleapis.com'",
            )
                ->withName($prefix . 'google-fonts-stylesheets'),
            WorkboxCacheStrategy::create(
                $this->workbox->enabled && $this->workbox->googleFontCache->enabled,
                true,
                CacheStrategyInterface::STRATEGY_CACHE_FIRST,
                "({url}) => url.origin === 'https://fonts.gstatic.com'"
            )
                ->withName($prefix . 'google-fonts-webfonts')
                ->withPlugin(
                    CacheableResponsePlugin::create(),
                    ExpirationPlugin::create(
                        $this->workbox->googleFontCache->maxEntries ?? 30,
                        $this->workbox->googleFontCache->maxAgeInSeconds() ?? 60 * 60 * 24 * 365
                    ),
                ),
        ];
    }
}
