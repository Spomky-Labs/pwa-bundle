<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class RelatedApplication
{
    public string $platform;

    public string $url;

    public null|string $id = null;
}
