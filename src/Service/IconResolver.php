<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\Icon;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use function array_key_exists;
use function assert;
use function count;
use function is_array;
use function sprintf;

final readonly class IconResolver
{
    public function __construct(
        private AssetMapperInterface $assetMapper,
        private ImageProcessorInterface $imageProcessor,
        #[Autowire(param: 'kernel.debug')]
        public bool $debug
    ) {
    }

    public function getIcon(Icon $icon): Data
    {
        $asset = $this->getAsset($icon->src);
        $content = $asset->content;
        if ($content === null) {
            $content = (new Filesystem())->readFile($asset->sourcePath);
        }

        $imageProcessor = fn (Configuration $configuration): string => $this->imageProcessor->process(
            $content,
            null,
            null,
            null,
            configuration: $configuration
        );
        $sizeList = $icon->sizeList;
        if (count($sizeList) === 0) {
            $sizeList = [0];
        }
        $size = max($sizeList);
        if ($size === 0) {
            $url = sprintf('/pwa/icon-any-%s.%s', $asset->digest, $asset->publicExtension);
            return new Data(
                $url,
                fn () => $content,
                [
                    'Cache-Control' => 'public, max-age=604800, immutable',
                    'Content-Type' => $this->getType($icon->type, $url),
                    'X-Pwa-Dev' => true,
                ],
            );
        }

        $configuration = new Configuration(
            $size,
            $size,
            $icon->format ?? $asset->publicExtension,
            $icon->backgroundColor,
            $icon->borderRadius,
            $icon->imageScale,
            str_contains($icon->purpose ?? '', 'monochrome'),
        );
        $format = $icon->format ?? $asset->publicExtension;
        $hash = hash(
            'xxh128',
            sprintf(
                '%s%s%s%d%s%s',
                $asset->digest,
                $configuration,
                $format,
                $size,
                $icon->purpose ?? '',
                $icon->type ?? '',
            )
        );
        $url = sprintf('/pwa/icon-%s.%s', $hash, $format);

        return new Data(
            $url,
            fn () => $imageProcessor($configuration),
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => $this->getType($icon->type, $url),
                'X-Pwa-Dev' => true,
            ],
        );
    }

    public function getType(?string $type, string $url): ?string
    {
        if ($type !== null) {
            return $type;
        }
        if (! class_exists(MimeTypes::class)) {
            return null;
        }
        $fileinfo = pathinfo($url);
        if (! array_key_exists('extension', $fileinfo)) {
            return null;
        }

        $mime = MimeTypes::getDefault();
        $mimeTypes = $mime->getMimeTypes($fileinfo['extension']);
        if ($mimeTypes === []) {
            return null;
        }

        return $mimeTypes[0];
    }

    private function getAsset(Asset $asset): MappedAsset
    {
        if (str_starts_with($asset->src, '/')) {
            $content = (new Filesystem())->readFile($asset->src);
            $hash = hash('xxh128', $content);
            $fileinfo = pathinfo($asset->src);
            assert(is_array($fileinfo), 'Invalid file.');
            assert(array_key_exists('filename', $fileinfo), 'Invalid file.');
            assert(array_key_exists('extension', $fileinfo), 'Invalid file.');
            assert(array_key_exists('basename', $fileinfo), 'Invalid file.');

            return new MappedAsset(
                sprintf('/pwa/%s-%s.%s', $hash, $fileinfo['filename'], $fileinfo['extension']),
                $asset->src,
                sprintf('/pwa/%s.%s', $hash, $fileinfo['extension']),
                sprintf('/pwa/%s-%s.%s', $hash, $fileinfo['filename'], $fileinfo['extension']),
                $content,
                $hash,
                false,
            );
        }
        $asset = $this->assetMapper->getAsset($asset->src);
        assert($asset instanceof MappedAsset, sprintf('Invalid asset "%s"', $asset->sourcePath));

        return $asset;
    }
}
