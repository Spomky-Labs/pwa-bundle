<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class ProtocolHandler
{
    public string $protocol;

    public null|string $placeholder = null;

    public Url $url;
}
