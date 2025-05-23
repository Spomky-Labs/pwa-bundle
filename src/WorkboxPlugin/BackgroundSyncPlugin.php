<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

use LogicException;
use function Symfony\Component\String\u;

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
        $queueId = u($this->queueName)
            ->snake()
            ->prepend('queue_')
            ->toString();
        $forceSyncFallback = $this->forceSyncFallback ? 'true' : 'false';
        $queueDeclaration = <<< QUEUE_DECLARATION
const {$queueId} = new workbox.backgroundSync.Queue('{$this->queueName}', {
  maxRetentionTime: {$this->maxRetentionTime},
  forceSyncFallback: {$forceSyncFallback},
});

QUEUE_DECLARATION;
        if ($this->broadcastChannel !== null) {
            $bcId = u($this->broadcastChannel)
                ->snake()
                ->prepend('bc_')
                ->toString();
            $queueDeclaration .= <<< QUEUE_DECLARATION
const {$bcId} = new BroadcastChannel('{$this->broadcastChannel}');
{$bcId}.onmessage = async (event) => {
  if (event.data?.type === 'status-request') {
    const entries = await {$queueId}.getAll();
    {$bcId}.postMessage({ name: '{$this->queueName}', remaining: entries.length });
  }

  if (event.data?.type === 'replay-request') {
    try {
      await {$queueId}.replayRequests();
      const entries = await {$queueId}.getAll();
      {$bcId}.postMessage({ name: '{$this->queueName}', replayed: true, remaining: entries.length });
    } catch (error) {
      const entries = await {$queueId}.getAll();
      {$bcId}.postMessage({ name: '{$this->queueName}', replayed: false, remaining: entries.length, error: error.message });
    }
  }
};

const {$pluginId} = {
  fetchDidFail: async ({ request }) => {
    await {$queueId}.pushRequest({ request });
    const entries = await {$queueId}.getAll();
    {$bcId}.postMessage({ name: '{$this->queueName}', remaining: entries.length });
  },
  onSync: async () => {
    try {
      await {$queueId}.replayRequests();
    } catch (e) {
    } finally {
      const entries = await {$queueId}.getAll();
      {$bcId}.postMessage({ name: '{$this->queueName}', remaining: entries.length });
    }
  }
};

QUEUE_DECLARATION;
        } else {
            $queueDeclaration .= <<< QUEUE_DECLARATION
const {$pluginId} = {
  fetchDidFail: async ({ request }) => {
    await {$queueId}.pushRequest({ request });
  }
};

QUEUE_DECLARATION;
        }

        return $queueDeclaration;
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
