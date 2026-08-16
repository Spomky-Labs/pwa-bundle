<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use const PHP_INT_MAX;

enum ScreenshotSize: string
{
    // Standard resolutions (landscape - wide)
    case HD = 'hd';                    // 1280x720
    case FULL_HD = 'fhd';              // 1920x1080
    case QHD = '2k';                   // 2560x1440
    case UHD = '4k';                   // 3840x2160
    case UHD_8K = '8k';                // 7680x4320

    // Portrait resolutions (narrow)
    case P_480 = '480p';               // 480x640
    case P_720 = '720p';               // 720x1280
    case P_1080 = '1080p';             // 1080x1920
    case P_1440 = '1440p';             // 1440x2560

    // iPhone models (narrow)
    case IPHONE_SE = 'iphone-se';      // 375x667
    case IPHONE_8 = 'iphone-8';        // 375x667
    case IPHONE_12 = 'iphone-12';      // 390x844
    case IPHONE_13 = 'iphone-13';      // 390x844
    case IPHONE_14 = 'iphone-14';      // 390x844
    case IPHONE_14_PLUS = 'iphone-14-plus';      // 428x926
    case IPHONE_14_PRO = 'iphone-14-pro';        // 393x852
    case IPHONE_14_PRO_MAX = 'iphone-14-pro-max'; // 430x932
    case IPHONE_15 = 'iphone-15';      // 393x852
    case IPHONE_15_PRO_MAX = 'iphone-15-pro-max'; // 430x932

    // Android popular devices (narrow)
    case PIXEL_5 = 'pixel-5';          // 393x851
    case PIXEL_6 = 'pixel-6';          // 412x915
    case PIXEL_7 = 'pixel-7';          // 412x915
    case PIXEL_7_PRO = 'pixel-7-pro';  // 412x915
    case GALAXY_S21 = 'galaxy-s21';    // 360x800
    case GALAXY_S22 = 'galaxy-s22';    // 360x800
    case GALAXY_S23 = 'galaxy-s23';    // 360x780

    // Tablets
    case IPAD = 'ipad';                // 768x1024 (portrait) - use /L for landscape, /LP for both
    case IPAD_PRO_11 = 'ipad-pro-11';  // 834x1194 (portrait) - use /L for landscape, /LP for both
    case IPAD_PRO_129 = 'ipad-pro-129'; // 1024x1366 (portrait) - use /L for landscape, /LP for both

    // Common web breakpoints (wide)
    case MOBILE_SM = 'mobile-sm';      // 320x568
    case MOBILE_MD = 'mobile-md';      // 375x667
    case MOBILE_LG = 'mobile-lg';      // 414x896
    case TABLET = 'tablet';            // 768x1024
    case DESKTOP_SM = 'desktop-sm';    // 1024x768
    case DESKTOP_MD = 'desktop-md';    // 1366x768
    case DESKTOP_LG = 'desktop-lg';    // 1920x1080
    case DESKTOP_XL = 'desktop-xl';    // 2560x1440

    /**
     * @return array{width: int, height: int}
     */
    public function getDimensions(): array
    {
        return match ($this) {
            // Standard resolutions (landscape)
            self::HD => [
                'width' => 1280,
                'height' => 720,
            ],
            self::FULL_HD => [
                'width' => 1920,
                'height' => 1080,
            ],
            self::QHD => [
                'width' => 2560,
                'height' => 1440,
            ],
            self::UHD => [
                'width' => 3840,
                'height' => 2160,
            ],
            self::UHD_8K => [
                'width' => 7680,
                'height' => 4320,
            ],

            // Portrait resolutions
            self::P_480 => [
                'width' => 480,
                'height' => 640,
            ],
            self::P_720 => [
                'width' => 720,
                'height' => 1280,
            ],
            self::P_1080 => [
                'width' => 1080,
                'height' => 1920,
            ],
            self::P_1440 => [
                'width' => 1440,
                'height' => 2560,
            ],

            // iPhone models
            self::IPHONE_SE, self::IPHONE_8 => [
                'width' => 375,
                'height' => 667,
            ],
            self::IPHONE_12, self::IPHONE_13, self::IPHONE_14 => [
                'width' => 390,
                'height' => 844,
            ],
            self::IPHONE_14_PLUS => [
                'width' => 428,
                'height' => 926,
            ],
            self::IPHONE_14_PRO, self::IPHONE_15 => [
                'width' => 393,
                'height' => 852,
            ],
            self::IPHONE_14_PRO_MAX, self::IPHONE_15_PRO_MAX => [
                'width' => 430,
                'height' => 932,
            ],

            // Android devices
            self::PIXEL_5 => [
                'width' => 393,
                'height' => 851,
            ],
            self::PIXEL_6, self::PIXEL_7, self::PIXEL_7_PRO => [
                'width' => 412,
                'height' => 915,
            ],
            self::GALAXY_S21, self::GALAXY_S22 => [
                'width' => 360,
                'height' => 800,
            ],
            self::GALAXY_S23 => [
                'width' => 360,
                'height' => 780,
            ],

            // Tablets
            self::IPAD => [
                'width' => 768,
                'height' => 1024,
            ],
            self::IPAD_PRO_11 => [
                'width' => 834,
                'height' => 1194,
            ],
            self::IPAD_PRO_129 => [
                'width' => 1024,
                'height' => 1366,
            ],

            // Web breakpoints
            self::MOBILE_SM => [
                'width' => 320,
                'height' => 568,
            ],
            self::MOBILE_MD => [
                'width' => 375,
                'height' => 667,
            ],
            self::MOBILE_LG => [
                'width' => 414,
                'height' => 896,
            ],
            self::TABLET => [
                'width' => 768,
                'height' => 1024,
            ],
            self::DESKTOP_SM => [
                'width' => 1024,
                'height' => 768,
            ],
            self::DESKTOP_MD => [
                'width' => 1366,
                'height' => 768,
            ],
            self::DESKTOP_LG => [
                'width' => 1920,
                'height' => 1080,
            ],
            self::DESKTOP_XL => [
                'width' => 2560,
                'height' => 1440,
            ],
        };
    }

    public function getFormFactor(): string
    {
        $dimensions = $this->getDimensions();
        return $dimensions['width'] > $dimensions['height'] ? 'wide' : 'narrow';
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::HD => 'HD (1280×720)',
            self::FULL_HD => 'Full HD (1920×1080)',
            self::QHD => '2K/QHD (2560×1440)',
            self::UHD => '4K/UHD (3840×2160)',
            self::UHD_8K => '8K (7680×4320)',

            self::P_480 => '480p Portrait (480×640)',
            self::P_720 => '720p Portrait (720×1280)',
            self::P_1080 => '1080p Portrait (1080×1920)',
            self::P_1440 => '1440p Portrait (1440×2560)',

            self::IPHONE_SE => 'iPhone SE (375×667)',
            self::IPHONE_8 => 'iPhone 8 (375×667)',
            self::IPHONE_12 => 'iPhone 12 (390×844)',
            self::IPHONE_13 => 'iPhone 13 (390×844)',
            self::IPHONE_14 => 'iPhone 14 (390×844)',
            self::IPHONE_14_PLUS => 'iPhone 14 Plus (428×926)',
            self::IPHONE_14_PRO => 'iPhone 14 Pro (393×852)',
            self::IPHONE_14_PRO_MAX => 'iPhone 14 Pro Max (430×932)',
            self::IPHONE_15 => 'iPhone 15 (393×852)',
            self::IPHONE_15_PRO_MAX => 'iPhone 15 Pro Max (430×932)',

            self::PIXEL_5 => 'Google Pixel 5 (393×851)',
            self::PIXEL_6 => 'Google Pixel 6 (412×915)',
            self::PIXEL_7 => 'Google Pixel 7 (412×915)',
            self::PIXEL_7_PRO => 'Google Pixel 7 Pro (412×915)',
            self::GALAXY_S21 => 'Samsung Galaxy S21 (360×800)',
            self::GALAXY_S22 => 'Samsung Galaxy S22 (360×800)',
            self::GALAXY_S23 => 'Samsung Galaxy S23 (360×780)',

            self::IPAD => 'iPad (768×1024)',
            self::IPAD_PRO_11 => 'iPad Pro 11" (834×1194)',
            self::IPAD_PRO_129 => 'iPad Pro 12.9" (1024×1366)',

            self::MOBILE_SM => 'Mobile Small (320×568)',
            self::MOBILE_MD => 'Mobile Medium (375×667)',
            self::MOBILE_LG => 'Mobile Large (414×896)',
            self::TABLET => 'Tablet (768×1024)',
            self::DESKTOP_SM => 'Desktop Small (1024×768)',
            self::DESKTOP_MD => 'Desktop Medium (1366×768)',
            self::DESKTOP_LG => 'Desktop Large (1920×1080)',
            self::DESKTOP_XL => 'Desktop XL (2560×1440)',
        };
    }

    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * @return array<string>
     */
    public static function getAllProfileNames(): array
    {
        return array_map(static fn (self $size) => $size->value, self::cases());
    }

    public static function getSuggestion(string $invalid): ?string
    {
        $all = self::getAllProfileNames();
        $shortest = null;
        $shortestDistance = PHP_INT_MAX;

        foreach ($all as $profile) {
            $distance = levenshtein($invalid, $profile);
            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $shortest = $profile;
            }
        }

        return $shortestDistance <= 3 ? $shortest : null;
    }
}
