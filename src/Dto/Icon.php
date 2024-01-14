<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class Icon
{
    public null|string $src = null;

    /**
     * @var array<int>
     */
    public array $sizes;

    public null|string $format = null;

    public null|string $purpose = null;
}
