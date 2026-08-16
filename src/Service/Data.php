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
     * @param string|Closure(): string $data
     * @param array<string, string|bool> $headers
     */
    public function __construct(
        public readonly string $url,
        private string|Closure $data,
        public readonly array $headers,
        public readonly null|string $html = null,
        public readonly bool $contextFree = true,
    ) {
    }

    /**
     * @param string|Closure(): string $data
     * @param array<string, string|bool> $headers
     */
    public static function create(
        string $url,
        string|Closure $data,
        array $headers = [],
        null|string $html = null,
        bool $contextFree = true,
    ): self {
        return new self($url, $data, $headers, $html, $contextFree);
    }

    public function getData(): string
    {
        $data = $this->data;
        if ($data instanceof Closure) {
            $data = $data();
            $this->data = $data;
        }

        return $data;
    }

    /**
     * @return string|Closure(): string
     */
    public function getRawData(): string|Closure
    {
        return $this->data;
    }
}
