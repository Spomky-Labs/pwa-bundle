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

    public function load(): string
    {
        $url = $this->assetMapper->getPublicPath($this->manifestPublicUrl) ?? $this->manifestPublicUrl;
        $output = sprintf('<link rel="manifest" href="%s">', $url);
        if ($this->manifest->themeColor !== null) {
            $output .= sprintf('%s<meta name="theme-color" content="%s">', PHP_EOL, $this->manifest->themeColor);
        }

        return $output;
    }
}
