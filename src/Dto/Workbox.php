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

    #[SerializedName('standard_rules_placeholder')]
    public string $standardRulesPlaceholder;

    #[SerializedName('offline_fallback_placeholder')]
    public string $offlineFallbackPlaceholder;

    #[SerializedName('widgets_placeholder')]
    public string $widgetsPlaceholder;

    #[SerializedName('page_fallback')]
    public null|Url $pageFallback = null;

    #[SerializedName('image_fallback')]
    public null|Asset $imageFallback = null;

    #[SerializedName('font_fallback')]
    public null|Asset $fontFallback = null;

    /**
     * @var array<Url>
     */
    #[SerializedName('warm_cache_urls')]
    public array $warmCacheUrls = [];

    #[SerializedName('network_timeout_seconds')]
    public int $networkTimeoutSeconds = 3;

    #[SerializedName('max_font_age')]
    public int $maxFontAge = 60 * 60 * 24 * 365;

    #[SerializedName('max_font_cache_entries')]
    public int $maxFontCacheEntries = 60;

    #[SerializedName('max_image_age')]
    public int $maxImageAge = 60 * 60 * 24 * 365;

    #[SerializedName('max_image_cache_entries')]
    public int $maxImageCacheEntries = 60;

    #[SerializedName('image_regex')]
    public string $imageRegex = '/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/';

    #[SerializedName('static_regex')]
    public string $staticRegex = '/\.(css|m?jsx?|json|xml|txt|map|webmanifest)$/';

    #[SerializedName('font_regex')]
    public string $fontRegex = '/\.(ttf|eot|otf|woff2)$/';

    #[SerializedName('clear_cache')]
    public bool $clearCache = true;

    #[SerializedName('image_cache_name')]
    public string $imageCacheName = 'images';

    #[SerializedName('font_cache_name')]
    public string $fontCacheName = 'fonts';

    #[SerializedName('page_cache_name')]
    public string $pageCacheName = 'pages';

    #[SerializedName('asset_cache_name')]
    public string $assetCacheName = 'assets';
}
