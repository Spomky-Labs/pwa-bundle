<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\WorkboxPlugin\CacheableResponsePlugin;
use SpomkyLabs\PwaBundle\WorkboxPlugin\ExpirationPlugin;

final class GoogleFontCache implements HasCacheStrategiesInterface, CanLogInterface
{
    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    public function __construct(
        ServiceWorker $serviceWorker,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->logger = new NullLogger();
    }

    public function getCacheStrategies(): array
    {
        $this->logger->debug('Getting cache strategies for Google Fonts');
        $prefix = $this->workbox->googleFontCache->cachePrefix ?? '';
        if ($prefix !== '') {
            $prefix .= '-';
        }

        $strategies = [
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
        $this->logger->debug('Google Fonts cache strategies', [
            'strategies' => $strategies,
        ]);

        return $strategies;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
