<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Workbox
{
    public bool $enabled = false;

    #[SerializedName('idb_public_url')]
    public string $indexDBPublicUrl = '/idb';

    #[SerializedName('cache_manifest')]
    public bool $cacheManifest = false;

    #[SerializedName('image_cache')]
    public ImageCache $imageCache;

    #[SerializedName('font_cache')]
    public FontCache $fontCache;

    /**
     * @var array<ResourceCache>
     */
    #[SerializedName('resource_caches')]
    public array $resourceCaches = [];

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

    /**
     * @deprecated since 1.6.0, will be removed in 2.0.0. Handle the "backgroundfetchsuccess"
     * event in your own service worker source instead.
     */
    #[SerializedName('background_fetch')]
    // @phpstan-ignore-next-line the deprecated type is the point: both go away in 2.0.0
    public null|BackgroundFetch $backgroundFetch = null;

    #[SerializedName('navigation_preload')]
    public bool $navigationPreload = false;

    /**
     * Switch controlling WorkboxDeprecatedHelpers. Not deprecated itself: it is the way out,
     * and the deprecation notice is triggered from the bundle extension when it is left to
     * true. Disappears in 2.0.0 along with the helpers it keeps.
     */
    #[SerializedName('keep_deprecated_helpers')]
    public bool $keepDeprecatedHelpers = true;

    public WorkboxConfig $config;

    public function __construct()
    {
        $this->config = new WorkboxConfig();
        $this->imageCache = new ImageCache();
        $this->fontCache = new FontCache();
        $this->assetCache = new AssetCache();
        $this->googleFontCache = new GoogleFontCache();
        $this->offlineFallback = new OfflineFallback();
    }
}
