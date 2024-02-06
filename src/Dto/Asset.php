<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class Asset
{
    public function __construct(
        public string $src
    ) {
    }

    public static function create(string $data): self
    {
        return new self($data);
    }
}
