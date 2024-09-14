<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function sprintf;

final class ManifestCache implements HasCacheStrategiesInterface, CanLogInterface
{
    private readonly string $manifestPublicUrl;

    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    public function __construct(
        ServiceWorker $serviceWorker,
        #[Autowire(param: 'spomky_labs_pwa.manifest.public_url')]
        string $manifestPublicUrl,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
        $this->logger = new NullLogger();
    }

    public function getCacheStrategies(): array
    {
        $strategies = [
            WorkboxCacheStrategy::create(
                $this->workbox->enabled && $this->workbox->cacheManifest,
                true,
                CacheStrategyInterface::STRATEGY_STALE_WHILE_REVALIDATE,
                sprintf("({url}) => '%s' === url.pathname", $this->manifestPublicUrl),
            )
                ->withName('manifest'),
        ];
        $this->logger->debug('Manifest cache strategies', [
            'strategies' => $strategies,
        ]);

        return $strategies;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
