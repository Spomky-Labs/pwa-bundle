<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use SpomkyLabs\PwaBundle\Service\MatchCallbackHandler\MatchCallbackHandler;
use SpomkyLabs\PwaBundle\Service\Plugin\BackgroundSyncPlugin;
use SpomkyLabs\PwaBundle\Service\WorkboxCacheStrategy;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final readonly class BackgroundSync implements HasCacheStrategies
{
    private Workbox $workbox;

    /**
     * @param iterable<MatchCallbackHandler> $matchCallbackHandlers
     */
    public function __construct(
        ServiceWorker $serviceWorker,
        #[TaggedIterator('spomky_labs_pwa.match_callback_handler')]
        private iterable $matchCallbackHandlers,
    ) {
        $this->workbox = $serviceWorker->workbox;
    }

    /**
     * @return array<CacheStrategy>
     */
    public function getCacheStrategies(): array
    {
        $strategies = [];
        foreach ($this->workbox->backgroundSync as $sync) {
            $strategies[] = WorkboxCacheStrategy::create(
                'BackgroundSync API',
                CacheStrategy::STRATEGY_NETWORK_ONLY,
                $this->prepareMatchCallback($sync->matchCallback),
                $this->workbox->enabled,
                true,
                null,
                [
                    BackgroundSyncPlugin::create(
                        $sync->queueName,
                        $sync->maxRetentionTime,
                        $sync->forceSyncFallback,
                        $sync->broadcastChannel
                    ),
                ]
            );
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
