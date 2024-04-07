<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

final readonly class BroadcastUpdatePlugin implements CachePluginInterface, HasDebugInterface
{
    private const NAME = 'BroadcastUpdatePlugin';

    /**
     * @var array<string>
     */
    private array $headersToCheck;

    /**
     * @param array<string> $headersToCheck
     */
    public function __construct(
        array $headersToCheck = []
    ) {
        $this->headersToCheck = $headersToCheck === [] ? ['Content-Type', 'ETag', 'Last-Modified'] : $headersToCheck;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function render(int $jsonOptions = 0): string
    {
        return sprintf(
            'new workbox.broadcastUpdate.BroadcastUpdatePlugin(%s)',
            json_encode([
                'headersToCheck' => $this->headersToCheck,
            ], $jsonOptions)
        );
    }

    /**
     * @param array<string, string> $headersToCheck
     */
    public static function create(array $headersToCheck = []): static
    {
        return new self($headersToCheck);
    }

    public function getDebug(): array
    {
        return [
            'headersToCheck' => $this->headersToCheck,
        ];
    }
}
