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

    #[SerializedName('cache_manifest')]
    public bool $cacheManifest;

    #[SerializedName('image_cache')]
    public ImageCache $imageCache;

    #[SerializedName('font_cache')]
    public FontCache $fontCache;

    /**
     * @var array<PageCache>
     */
    #[SerializedName('resource_caches')]
    public array $resourceCaches;

    #[SerializedName('asset_cache')]
    public AssetCache $assetCache;

    #[SerializedName('google_fonts')]
    public GoogleFontCache $googleFontCache;

    #[SerializedName('offline_fallback')]
    public OfflineFallback $offlineFallback;

    /**
     * @var array<BackgroundSync>
     */
    #[SerializedName('background_sync')]
    public array $backgroundSync = [];

    #[SerializedName('clear_cache')]
    public bool $clearCache = true;
}
