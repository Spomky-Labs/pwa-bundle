<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use SpomkyLabs\PwaBundle\Dto\Icon;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\MimeTypes;
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
                ['url' => $url, 'format' => $format] = $this->getIconInfo($icon);
                $attributes = sprintf(
                    'rel="%s" sizes="%s" href="%s"',
                    str_contains($icon->purpose ?? '', 'maskable') ? 'mask-icon' : 'icon',
                    $icon->getSizeList(),
                    $url
                );
                if ($format !== null) {
                    $attributes .= sprintf(' type="%s"', $format);
                }

                $output .= sprintf('%s<link %s>', PHP_EOL, $attributes);
            }
        }
        if ($this->manifest->themeColor !== null && $themeColor === true) {
            $output .= sprintf('%s<meta name="theme-color" content="%s">', PHP_EOL, $this->manifest->themeColor);
        }

        return $output;
    }

    /**
     * @return array{url: string, format: string|null}
     */
    private function getIconInfo(Icon $icon): array
    {
        $url = null;
        $format = $icon->format;
        if (! str_starts_with($icon->src, '/')) {
            $asset = $this->assetMapper->getAsset($icon->src);
            $url = $asset?->publicPath;
            $format = $this->getFormat($icon, $asset);
        }
        if ($url === null) {
            $url = $icon;
        }

        return [
            'url' => $url,
            'format' => $format,
        ];
    }

    private function getFormat(Icon $object, ?MappedAsset $asset): ?string
    {
        if ($object->format !== null) {
            return $object->format;
        }

        if ($asset === null || ! class_exists(MimeTypes::class)) {
            return null;
        }

        $mime = MimeTypes::getDefault();
        return $mime->guessMimeType($asset->sourcePath);
    }
}
