<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class Url
{
    public string $path;

    /**
     * @var array<string, mixed>
     */
    public array $params = [];
}
