<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

final readonly class CacheableResponsePlugin implements CachePluginInterface, HasDebugInterface
{
    private const NAME = 'CacheableResponsePlugin';

    /**
     * @var array{options?: array{statuses: array<int>, headers?: array<string, string>}}
     */
    private array $options;

    /**
     * @param array<int> $statuses
     * @param array<string, string> $headers
     */
    public function __construct(array $statuses = [0, 200], array $headers = [])
    {
        $options = array_filter([
            'statuses' => $statuses,
            'headers' => $headers,
        ], fn ($value) => $value !== []);
        $this->options = $options === [] ? [
            'statuses' => [0, 200],
        ] : $options;
    }

    public function render(int $jsonOptions = 0): string
    {
        return sprintf(
            'new workbox.cacheableResponse.CacheableResponsePlugin(%s)',
            json_encode($this->options, $jsonOptions)
        );
    }

    /**
     * @param array<int> $statuses
     * @param array<string, string> $headers
     */
    public static function create(array $statuses = [0, 200], array $headers = []): static
    {
        return new self($statuses, $headers);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDebug(): array
    {
        return [
            'statuses' => $this->options['statuses'] ?? [],
            'headers' => $this->options['headers'] ?? [],
        ];
    }
}
