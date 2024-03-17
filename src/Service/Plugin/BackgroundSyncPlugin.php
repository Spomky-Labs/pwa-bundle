<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Plugin;

final readonly class BackgroundSyncPlugin extends CachePlugin
{
    public function render(int $jsonOptions = 0): string
    {
        $forceSyncFallback = $this->options['forceSyncFallback'] === true ? 'true' : 'false';
        $broadcastChannel = $this->options['broadcastChannel'];
        $maxRetentionTime = $this->options['maxRetentionTime'];
        $queueName = $this->options['queueName'];
        $broadcastChannelSection = '';
        if ($broadcastChannel !== null) {
            $broadcastChannelSection = <<<BROADCAST_CHANNEL
, "onSync": async ({queue}) => {
    try {
        await queue.replayRequests();
    } catch (error) {
        // Failed to replay one or more requests
    } finally {
        remainingRequests = await queue.getAll();
        const bc = new BroadcastChannel('{$broadcastChannel}');
        bc.postMessage({name: '{$queueName}', remaining: remainingRequests.length});
        bc.close();
    }
}
BROADCAST_CHANNEL;
        }

        $declaration = <<<BACKGROUND_SYNC_RULE_STRATEGY
new workbox.backgroundSync.BackgroundSyncPlugin('{$queueName}',{
    "maxRetentionTime": {$maxRetentionTime},
    "forceSyncFallback": {$forceSyncFallback}{$broadcastChannelSection}
})
BACKGROUND_SYNC_RULE_STRATEGY;

        return trim($declaration);
    }

    public static function create(
        string $queueName,
        int $maxRetentionTime,
        bool $forceSyncFallback,
        ?string $broadcastChannel
    ): static {
        return new self(
            'BackgroundSyncPlugin',
            [
                'queueName' => $queueName,
                'maxRetentionTime' => $maxRetentionTime,
                'forceSyncFallback' => $forceSyncFallback,
                'broadcastChannel' => $broadcastChannel,
            ]
        );
    }
}
