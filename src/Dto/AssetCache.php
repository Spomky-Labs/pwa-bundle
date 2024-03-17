<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class AssetCache extends Cache
{
    public bool $enabled = true;

    public string $regex = '/\.(css|js|json|xml|txt|map|ico|png|jpe?g|gif|svg|webp|bmp)$/';
}
