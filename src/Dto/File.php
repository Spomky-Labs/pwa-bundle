<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class File
{
    public string $name;

    /**
     * @var string[]
     */
    public array $accept;
}
