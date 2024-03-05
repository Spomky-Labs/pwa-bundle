<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class FontCache
{
    public bool $enabled = true;

    #[SerializedName('cache_name')]
    public string $cacheName = 'fonts';

    #[SerializedName('regex')]
    public string $regex = '/\.(ttf|eot|otf|woff2)$/';

    #[SerializedName('max_entries')]
    public int $maxEntries = 60;

    #[SerializedName('max_age')]
    public int $maxAge = 60;
}
