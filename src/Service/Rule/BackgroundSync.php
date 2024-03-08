<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use const PHP_EOL;

final readonly class BackgroundSync implements ServiceWorkerRule, HasCacheStrategies
{
    private Workbox $workbox;

    public function __construct(ServiceWorker $serviceWorker)
    {
        $this->workbox = $serviceWorker->workbox;
    }

    public function process(string $body): string
    {
        if ($this->workbox->enabled === false) {
            return $body;
        }
        if ($this->workbox->backgroundSync === []) {
            return $body;
        }

        $declaration = '';
        foreach ($this->workbox->backgroundSync as $sync) {
            $forceSyncFallback = $sync->forceSyncFallback === true ? 'true' : 'false';
            $broadcastChannel = '';
            if ($sync->broadcastChannel !== null) {
                $broadcastChannel = <<<BROADCAST_CHANNEL
,
    "onSync": async ({queue}) => {
        try {
            await queue.replayRequests();
        } catch (error) {
            // Failed to replay one or more requests
        } finally {
            remainingRequests = await queue.getAll();
            const bc = new BroadcastChannel('{$sync->broadcastChannel}');
            bc.postMessage({name: '{$sync->queueName}', remaining: remainingRequests.length});
            bc.close();
        }
    }
BROADCAST_CHANNEL;
            }
            $declaration .= <<<BACKGROUND_SYNC_RULE_STRATEGY
workbox.routing.registerRoute(
    new RegExp('{$sync->regex}'),
    new workbox.strategies.NetworkOnly({plugins: [new workbox.backgroundSync.BackgroundSyncPlugin('{$sync->queueName}',{
    "maxRetentionTime": {$sync->maxRetentionTime},
    "forceSyncFallback": {$forceSyncFallback}{$broadcastChannel}
})] }),
    '{$sync->method}'
);
BACKGROUND_SYNC_RULE_STRATEGY;
        }

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    public function getCacheStrategies(): array
    {
        $strategies = [];
        foreach ($this->workbox->backgroundSync as $sync) {
            $strategies[] = CacheStrategy::create(
                'backgroundSync',
                CacheStrategy::STRATEGY_NETWORK_ONLY,
                $sync->regex,
                $this->workbox->enabled,
                true,
                [
                    'maxTimeout' => 0,
                    'maxAge' => 0,
                    'maxEntries' => 0,
                ]
            );
        }

        return $strategies;
    }
}
