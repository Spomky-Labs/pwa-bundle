<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class GoogleFontCache
{
    public bool $enabled;

    #[SerializedName('cache_prefix')]
    public null|string $cachePrefix = null;

    #[SerializedName('max_age')]
    public null|int $maxAge = null;

    #[SerializedName('max_entries')]
    public null|int $maxEntries = null;
}
