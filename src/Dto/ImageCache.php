<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ImageCache
{
    public bool $enabled = true;

    #[SerializedName('cache_name')]
    public string $cacheName = 'assets';

    #[SerializedName('regex')]
    public string $regex = '/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/';

    #[SerializedName('max_entries')]
    public int $maxEntries = 60;

    #[SerializedName('max_age')]
    public int $maxAge = 60;
}
