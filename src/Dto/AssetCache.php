<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class AssetCache
{
    public bool $enabled = true;

    #[SerializedName('cache_name')]
    public string $cacheName = 'assets';

    public string $regex = '/\.(css|js|json|xml|txt|map|ico|png|jpe?g|gif|svg|webp|bmp)$/';
}
