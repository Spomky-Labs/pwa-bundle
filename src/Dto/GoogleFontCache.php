<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class GoogleFontCache extends Cache
{
    public bool $enabled;

    #[SerializedName('cache_prefix')]
    public null|string $cachePrefix = null;
}
