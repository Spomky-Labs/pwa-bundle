<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\MatchCallbackHandler\MatchCallbackHandlerInterface;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\WorkboxPlugin\BackgroundSyncPlugin;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class BackgroundSync implements HasCacheStrategiesInterface, CanLogInterface
{
    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    /**
     * @param iterable<MatchCallbackHandlerInterface> $matchCallbackHandlers
     */
    public function __construct(
        ServiceWorker $serviceWorker,
        #[AutowireIterator('spomky_labs_pwa.match_callback_handler')]
        private readonly iterable $matchCallbackHandlers,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->logger = new NullLogger();
    }

    /**
     * @return array<CacheStrategyInterface>
     */
    public function getCacheStrategies(): array
    {
        $this->logger->debug('Getting cache strategies for background sync');
        $strategies = [];
        foreach ($this->workbox->backgroundSync as $sync) {
            $strategies[] = WorkboxCacheStrategy::create(
                $this->workbox->enabled,
                true,
                CacheStrategyInterface::STRATEGY_NETWORK_ONLY,
                $this->prepareMatchCallback($sync->matchCallback)
            )
                ->withName('Background Sync')
                ->withPlugin(
                    BackgroundSyncPlugin::create(
                        $sync->queueName,
                        $sync->maxRetentionTime,
                        $sync->forceSyncFallback,
                        $sync->broadcastChannel,
                        $sync->errorOn4xx,
                        $sync->errorOn5xx,
                        $sync->expectRedirect,
                        $sync->expectedStatusCodes,
                    ),
                )
                ->withMethod($sync->method);
        }
        $this->logger->debug('Background sync strategies', [
            'strategies' => $strategies,
        ]);

        return $strategies;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function prepareMatchCallback(string $matchCallback): string
    {
        foreach ($this->matchCallbackHandlers as $handler) {
            if ($handler->supports($matchCallback)) {
                return $handler->handle($matchCallback);
            }
        }

        return $matchCallback;
    }
}
