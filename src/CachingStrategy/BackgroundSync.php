<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\MatchCallbackHandler\MatchCallbackHandlerInterface;
use SpomkyLabs\PwaBundle\WorkboxPlugin\BackgroundSyncPlugin;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final readonly class BackgroundSync implements HasCacheStrategiesInterface
{
    private Workbox $workbox;

    /**
     * @param iterable<MatchCallbackHandlerInterface> $matchCallbackHandlers
     */
    public function __construct(
        ServiceWorker $serviceWorker,
        #[TaggedIterator('spomky_labs_pwa.match_callback_handler')]
        private iterable $matchCallbackHandlers,
    ) {
        $this->workbox = $serviceWorker->workbox;
    }

    /**
     * @return array<CacheStrategyInterface>
     */
    public function getCacheStrategies(): array
    {
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
                        $sync->broadcastChannel
                    ),
                )
                ->withMethod($sync->method);
        }

        return $strategies;
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
