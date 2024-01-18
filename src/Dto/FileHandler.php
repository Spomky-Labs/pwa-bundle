<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class FileHandler
{
    public Url $action;

    /**
     * @var array<string, string[]>
     */
    public array $accept;
}
