<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

use LogicException;

final readonly class BackgroundSyncPlugin implements CachePluginInterface, HasBodyInterface, HasDebugInterface
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

    public function renderBody(string $pluginId, int $jsonOptions = 0): string
    {
        $forceSyncFallback = $this->forceSyncFallback ? 'true' : 'false';
        if ($this->broadcastChannel !== null) {
            return <<< DECLARATION
const {$pluginId} = createBackgroundSyncPluginWithBroadcast('{$this->queueName}', '{$this->broadcastChannel}', {$this->maxRetentionTime}, {$forceSyncFallback});

DECLARATION;
        }

        return <<< DECLARATION
const {$pluginId} = createBackgroundSyncPlugin('{$this->queueName}', {$this->maxRetentionTime}, {$forceSyncFallback});

DECLARATION;
    }

    public function render(int $jsonOptions = 0): string
    {
        throw new LogicException('Should never be called as the plugin uses a body.');
    }

    public static function create(
        string $queueName,
        int $maxRetentionTime,
        bool $forceSyncFallback,
        null|string $broadcastChannel,
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
