<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

final readonly class CacheableResponsePlugin extends CachePlugin
{
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
        $options = array_filter([
            'statuses' => $statuses,
            'headers' => $headers,
        ], fn ($value) => $value !== []);
        $options = $options === [] ? [
            'statuses' => [0, 200],
        ] : $options;

        return new self('CacheableResponsePlugin', $options);
    }
}
