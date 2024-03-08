<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\Workbox;
use const PHP_EOL;

final readonly class BackgroundSync implements WorkboxRule
{
    public function process(Workbox $workbox, string $body): string
    {
        if ($workbox->backgroundSync === []) {
            return $body;
        }

        $declaration = '';
        foreach ($workbox->backgroundSync as $sync) {
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
}
