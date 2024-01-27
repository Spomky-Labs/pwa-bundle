<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Workbox
{
    public bool $enabled;

    #[SerializedName('use_cdn')]
    public bool $useCDN;

    public string $version;

    #[SerializedName('workbox_public_url')]
    public string $workboxPublicUrl;

    #[SerializedName('workbox_import_placeholder')]
    public string $workboxImportPlaceholder;

    #[SerializedName('warm_cache_placeholder')]
    public string $warmCachePlaceholder;

    #[SerializedName('precaching_placeholder')]
    public string $precachingPlaceholder;

    #[SerializedName('offline_fallback_placeholder')]
    public string $offlineFallbackPlaceholder;

    #[SerializedName('widgets_placeholder')]
    public string $widgetsPlaceholder;

    #[SerializedName('offline_fallback')]
    public null|Url $offlineFallback = null;

    /**
     * @var array<Url>
     */
    #[SerializedName('warm_cache_urls')]
    public array $warmCacheUrls = [];
}
