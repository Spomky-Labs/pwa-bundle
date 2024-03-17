<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use SpomkyLabs\PwaBundle\Service\Plugin\CacheableResponsePlugin;
use SpomkyLabs\PwaBundle\Service\Plugin\ExpirationPlugin;
use SpomkyLabs\PwaBundle\Service\WorkboxCacheStrategy;

final readonly class GoogleFontCache implements HasCacheStrategies
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
                $prefix . 'google-fonts-stylesheets',
                CacheStrategy::STRATEGY_STALE_WHILE_REVALIDATE,
                "({url}) => url.origin === 'https://fonts.googleapis.com'",
                $this->workbox->enabled && $this->workbox->googleFontCache->enabled,
                true
            ),
            WorkboxCacheStrategy::create(
                $prefix . 'google-fonts-webfonts',
                CacheStrategy::STRATEGY_CACHE_FIRST,
                "({url}) => url.origin === 'https://fonts.gstatic.com'",
                $this->workbox->enabled && $this->workbox->googleFontCache->enabled,
                true,
                null,
                [
                    CacheableResponsePlugin::create(),
                    ExpirationPlugin::create(
                        $this->workbox->googleFontCache->maxAgeInSeconds() ?? 60 * 60 * 24 * 365,
                        $this->workbox->googleFontCache->maxEntries ?? 30
                    ),
                ]
            ),
        ];
    }
}
