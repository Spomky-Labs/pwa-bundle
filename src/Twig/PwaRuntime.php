<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use const PHP_EOL;

final readonly class PwaRuntime
{
    private string $manifestPublicUrl;

    public function __construct(
        private AssetMapperInterface $assetMapper,
        private Manifest $manifest,
        #[Autowire('%spomky_labs_pwa.manifest_public_url%')]
        string $manifestPublicUrl,
    ) {
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
    }

    public function load(bool $themeColor = true, bool $icons = true): string
    {
        $url = $this->assetMapper->getPublicPath($this->manifestPublicUrl) ?? $this->manifestPublicUrl;
        $output = sprintf('<link rel="manifest" href="%s">', $url);
        if ($this->manifest->icons !== [] && $icons === true) {
            foreach ($this->manifest->icons as $icon) {
                $iconUrl = $this->getIconPublicUrl($icon->src);
                $output .= sprintf(
                    '%s<link rel="%s" sizes="%s" href="%s">',
                    PHP_EOL,
                    str_contains($icon->purpose ?? '', 'maskable') ? 'mask-icon' : 'icon',
                    $icon->getSizeList(),
                    $iconUrl
                );
            }
        }
        if ($this->manifest->themeColor !== null && $themeColor === true) {
            $output .= sprintf('%s<meta name="theme-color" content="%s">', PHP_EOL, $this->manifest->themeColor);
        }

        return $output;
    }

    private function getIconPublicUrl(string $source): ?string
    {
        $url = null;
        if (! str_starts_with($source, '/')) {
            $asset = $this->assetMapper->getAsset($source);
            $url = $asset?->publicPath;
        }
        if ($url === null) {
            $url = $source;
        }

        return $url;
    }
}
