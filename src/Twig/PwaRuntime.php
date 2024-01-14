<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use RuntimeException;
use Symfony\Component\AssetMapper\AssetMapperInterface;

final readonly class PwaRuntime
{
    public function __construct(
        private AssetMapperInterface $assetMapper
    ) {
    }

    public function load(string $filename = 'site.webmanifest'): string
    {
        $url = $this
            ->assetMapper
            ->getAsset($filename)
            ->publicPath
        ;
        if ($url === null) {
            throw new RuntimeException(sprintf('The asset "%s" is missing.', $filename));
        }

        return sprintf('<link rel="manifest" href="%s">', $url);
    }
}
