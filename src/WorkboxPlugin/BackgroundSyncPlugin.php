<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

final readonly class BackgroundSyncPlugin implements CachePluginInterface, HasDebugInterface
{
    private const NAME = 'BackgroundSyncPlugin';

    public function __construct(
        public string $queueName,
        public bool $forceSyncFallback,
        public null|string $broadcastChannel,
        public int $maxRetentionTime,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function render(int $jsonOptions = 0): string
    {
        $forceSyncFallback = $this->forceSyncFallback === true ? 'true' : 'false';
        $broadcastChannelSection = '';
        if ($this->broadcastChannel !== null) {
            $broadcastChannelSection = <<<BROADCAST_CHANNEL
, "onSync": async ({queue}) => {
    try {
        await queue.replayRequests();
    } catch (error) {
        // Failed to replay one or more requests
    } finally {
        remainingRequests = await queue.getAll();
        const bc = new BroadcastChannel('{$this->broadcastChannel}');
        bc.postMessage({name: '{$this->queueName}', remaining: remainingRequests.length});
        bc.close();
    }
}
BROADCAST_CHANNEL;
        }

        $declaration = <<<BACKGROUND_SYNC_RULE_STRATEGY
new workbox.backgroundSync.BackgroundSyncPlugin('{$this->queueName}',{
    "maxRetentionTime": {$this->maxRetentionTime},
    "forceSyncFallback": {$forceSyncFallback}{$broadcastChannelSection}
})
BACKGROUND_SYNC_RULE_STRATEGY;

        return trim($declaration);
    }

    public static function create(
        string $queueName,
        int $maxRetentionTime,
        bool $forceSyncFallback,
        null|string $broadcastChannel
    ): static {
        return new self($queueName, $forceSyncFallback, $broadcastChannel, $maxRetentionTime);
    }

    public function getDebug(): array
    {
        return [
            'queueName' => $this->queueName,
            'forceSyncFallback' => $this->forceSyncFallback,
            'broadcastChannel' => $this->broadcastChannel,
            'maxRetentionTime' => $this->maxRetentionTime,
        ];
    }
}
