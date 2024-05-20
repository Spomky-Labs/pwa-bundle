<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Closure;

/**
 * @internal
 */
final class Data
{
    /**
     * @param array<string, string|bool> $headers
     */
    public function __construct(
        public readonly string $url,
        private string|Closure $data,
        public readonly array $headers,
        public readonly null|string $html = null,
    ) {
    }

    /**
     * @param array<string, string|bool> $headers
     */
    public static function create(
        string $url,
        string|Closure $data,
        array $headers = [],
        null|string $html = null
    ): self {
        return new self($url, $data, $headers, $html);
    }

    public function getData(): string
    {
        if ($this->data instanceof Closure) {
            $this->data = ($this->data)();
        }

        return $this->data;
    }

    public function getRawData(): string|Closure
    {
        return $this->data;
    }
}
