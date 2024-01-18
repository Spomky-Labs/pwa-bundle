<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class RelatedApplication
{
    public string $platform;

    public Url $url;

    public null|string $id = null;
}
