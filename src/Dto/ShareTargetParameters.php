<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class ShareTargetParameters
{
    public null|string $title = null;

    public null|string $text = null;

    public null|string $url = null;

    /**
     * @var array<File>
     */
    public array $files = [];
}
