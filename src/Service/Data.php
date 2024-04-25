<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

/**
 * @internal
 */
final readonly class Data
{
    /**
     * @param string[] $headers
     */
    public function __construct(
        public string $url,
        public string $data,
        public array $headers
    ){
    }

    /**
     * @param array<string, string> $headers
     */
    public static function create(string $url, string $data, array $headers = []): self
    {
        return new self($url, $data, $headers);
    }
}
