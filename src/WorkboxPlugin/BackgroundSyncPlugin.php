<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

use function count;

final readonly class BackgroundSyncPlugin implements CachePluginInterface, HasDebugInterface
{
    private const NAME = 'BackgroundSyncPlugin';

    /**
     * @param array<int> $expectedStatusCodes
     */
    public function __construct(
        public string $queueName,
        public bool $forceSyncFallback,
        public null|string $broadcastChannel,
        public int $maxRetentionTime,
        public bool $errorOn4xx = false,
        public bool $errorOn5xx = true,
        public bool $expectRedirect = false,
        public array $expectedStatusCodes = [],
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

        $errorOn4xx = $this->getErrorOn4xx();
        $errorOn5xx = $this->getErrorOn5xx();
        $expectRedirect = $this->getExpectRedirect();
        $expectedStatusCodes = $this->getExpectedSuccessStatusCodes();

        $declaration = <<<BACKGROUND_SYNC_RULE_STRATEGY

        {$errorOn4xx}{$errorOn5xx}{$expectRedirect}{$expectedStatusCodes}new workbox.backgroundSync.BackgroundSyncPlugin('{$this->queueName}',{"maxRetentionTime": {$this->maxRetentionTime}, "forceSyncFallback": {$forceSyncFallback}{$broadcastChannelSection}})

BACKGROUND_SYNC_RULE_STRATEGY;

        return trim($declaration);
    }

    /**
     * @param array<int> $expectedStatusCodes
     */
    public static function create(
        string $queueName,
        int $maxRetentionTime,
        bool $forceSyncFallback,
        null|string $broadcastChannel,
        bool $errorOn4xx = false,
        bool $errorOn5xx = true,
        bool $expectRedirect = false,
        array $expectedStatusCodes = [],
    ): static {
        return new self(
            $queueName,
            $forceSyncFallback,
            $broadcastChannel,
            $maxRetentionTime,
            $errorOn4xx,
            $errorOn5xx,
            $expectRedirect,
            $expectedStatusCodes,
        );
    }

    public function getDebug(): array
    {
        return [
            'queueName' => $this->queueName,
            'forceSyncFallback' => $this->forceSyncFallback,
            'broadcastChannel' => $this->broadcastChannel,
            'maxRetentionTime' => $this->maxRetentionTime,
            'errorOn4xx' => $this->errorOn4xx,
            'errorOn5xx' => $this->errorOn5xx,
            'expectRedirect' => $this->expectRedirect,
            'expectedSuccessStatusCodes' => $this->expectedStatusCodes,
        ];
    }

    private function getErrorOn4xx(): string
    {
        if ($this->errorOn5xx === false) {
            return '';
        }

        return $this->getErrorOn(400);
    }

    private function getErrorOn5xx(): string
    {
        if ($this->errorOn5xx === false) {
            return '';
        }

        return $this->getErrorOn(500);
    }

    private function getErrorOn(int $statusCode): string
    {
        return "{fetchDidSucceed: ({response}) => {if (response.status >= {$statusCode}) {throw new Error('Server error.');}return response;}},";
    }

    private function getExpectedSuccessStatusCodes(): string
    {
        if (count($this->expectedStatusCodes) === 0) {
            return '';
        }
        $codes = implode(',', $this->expectedStatusCodes);

        return "{fetchDidSucceed: ({response}) => {if (! [{$codes}].includes(response.status)) {throw new Error('Unexpected response status code. Expected one of [{$codes}]. Got ' + response.status);}return response;}},";
    }

    private function getExpectRedirect(): string
    {
        if ($this->expectRedirect === false) {
            return '';
        }

        return "{fetchDidSucceed: ({response}) => {if (response.type !== 'opaqueredirect' || response.redirect !== true) {throw new Error('Expected a redirect response.');}return response;}},";
    }
}
