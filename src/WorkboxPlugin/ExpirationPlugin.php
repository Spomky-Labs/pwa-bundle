<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

use function sprintf;
use const JSON_THROW_ON_ERROR;

final readonly class ExpirationPlugin implements CachePluginInterface, HasDebugInterface
{
    private const NAME = 'ExpirationPlugin';

    /**
     * @var array{maxEntries: null|int, maxAgeSeconds: null|int}
     */
    private array $options;

    public function __construct(null|int $maxEntries, null|int $maxAgeSeconds)
    {
        $this->options = [
            'maxEntries' => $maxEntries,
            'maxAgeSeconds' => $maxAgeSeconds,
        ];
    }

    public function render(int $jsonOptions = 0): string
    {
        return sprintf(
            'new workbox.expiration.ExpirationPlugin(%s)',
            json_encode($this->options, JSON_THROW_ON_ERROR | $jsonOptions)
        );
    }

    public static function create(null|int $maxEntries, null|int $maxAgeSeconds): static
    {
        return new self($maxEntries, $maxAgeSeconds);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{maxEntries: null|int, maxAgeSeconds: null|int}
     */
    public function getDebug(): array
    {
        return [
            'maxEntries' => $this->options['maxEntries'] ?? null,
            'maxAgeSeconds' => $this->options['maxAgeSeconds'] ?? null,
        ];
    }
}
