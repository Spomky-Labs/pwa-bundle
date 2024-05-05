<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function assert;
use const PHP_EOL;

final class FaviconsCompiler implements FileCompilerInterface
{
    /**
     * @var null|array<string, Data>
     */
    private null|array $files = null;

    public function __construct(
        private readonly null|ImageProcessorInterface $imageProcessor,
        private readonly Favicons $favicons,
        private readonly AssetMapperInterface $assetMapper,
        #[Autowire('%kernel.debug%')]
        public readonly bool $debug,
    ) {
    }

    /**
     * @return array<string, Data>
     */
    public function getFiles(): array
    {
        if ($this->files !== null) {
            return $this->files;
        }
        if ($this->imageProcessor === null || $this->favicons->enabled === false) {
            return [];
        }
        $asset = $this->assetMapper->getAsset($this->favicons->src->src);
        assert($asset !== null, 'The asset does not exist.');
        $this->files = [];
        $sizes = [
            //Always
            [
                'url' => '/favicon.ico',
                'width' => 16,
                'height' => 16,
                'format' => 'ico',
                'mimetype' => 'image/x-icon',
                'rel' => 'icon',
            ],
            [
                'url' => '/favicons/icon-%sx%s.{hash}.png',
                'width' => 16,
                'height' => 16,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            [
                'url' => '/favicons/icon-%sx%s.{hash}.png',
                'width' => 32,
                'height' => 32,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            //High resolution iOS
            [
                'url' => '/favicons/icon-%sx%s.{hash}.png',
                'width' => 180,
                'height' => 180,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'apple-touch-icon',
            ],
            //High resolution chrome
            [
                'url' => '/favicons/icon-%sx%s.{hash}.png',
                'width' => 192,
                'height' => 192,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            [
                'url' => '/favicons/icon-%sx%s.{hash}.png',
                'width' => 512,
                'height' => 512,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
        ];
        if ($this->favicons->lowResolution === true) {
            $sizes = [
                ...$sizes,
                //Prior iOS 6
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 57,
                    'height' => 57,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 72,
                    'height' => 72,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 114,
                    'height' => 114,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],

                //Prior iOS 7
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 60,
                    'height' => 60,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 76,
                    'height' => 76,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 120,
                    'height' => 120,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 152,
                    'height' => 152,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],

                //Other resolution
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 36,
                    'height' => 36,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 48,
                    'height' => 48,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 72,
                    'height' => 72,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 96,
                    'height' => 96,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 144,
                    'height' => 144,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 256,
                    'height' => 256,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%sx%s.{hash}.png',
                    'width' => 384,
                    'height' => 384,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
            ];
        }

        foreach ($sizes as $size) {
            $configuration = Configuration::create(
                $size['width'],
                $size['height'],
                $size['format'],
                $this->favicons->backgroundColor,
                $this->favicons->borderRadius,
                $this->favicons->imageScale,
            );
            $this->files[sprintf($size['url'], $size['width'], $size['height'])] = $this->processIcon(
                $asset,
                sprintf($size['url'], $size['width'], $size['height']),
                $configuration,
                $size['mimetype'],
                $size['rel'],
            );
        }
        if ($this->favicons->tileColor !== null) {
            $this->files = [...$this->files, ...$this->processBrowserConfig($asset)];
        }

        return $this->files;
    }

    private function processIcon(
        MappedAsset $asset,
        string $publicUrl,
        Configuration $configuration,
        string $mimeType,
        null|string $rel,
    ): Data {
        $content = file_get_contents($asset->sourcePath);
        assert($content !== false);
        if ($this->debug === true) {
            $data = $this->imageProcessor->process($content, null, null, null, $configuration);
            $url = str_replace('{hash}', '', $publicUrl);
            $html = $rel === null ? null : sprintf(
                '<link rel="%s" sizes="%dx%d" type="%s" href="%s">',
                $rel,
                $configuration->width,
                $configuration->height,
                $mimeType,
                $url
            );
            return Data::create(
                $url,
                $data,
                [
                    'Cache-Control' => 'public, max-age=604800, immutable',
                    'Content-Type' => $mimeType,
                    'X-Favicons-Dev' => true,
                ],
                $html
            );
        }
        assert($this->imageProcessor !== null);
        $data = $this->imageProcessor->process($content, null, null, null, $configuration);
        $url = str_replace('{hash}', hash('xxh128', $data), $publicUrl);
        return Data::create(
            $url,
            $data,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => $mimeType,
                'X-Favicons-Dev' => true,
                'Etag' => hash('xxh128', $data),
            ],
            sprintf(
                '<link rel="%s" sizes="%dx%d" type="%s" href="%s">',
                $rel,
                $configuration->width,
                $configuration->height,
                $mimeType,
                $url
            )
        );
    }

    /**
     * @return array<Data>
     */
    private function processBrowserConfig(MappedAsset $asset): array
    {
        $icon70x70 = $this->processIcon(
            $asset,
            '/favicons/icon-70x70.{hash}.png',
            Configuration::create(70, 70, 'png', null, null, $this->favicons->imageScale),
            'image/png',
            null
        );
        $icon150x150 = $this->processIcon(
            $asset,
            '/favicons/icon-150x150.{hash}.png',
            Configuration::create(150, 150, 'png', null, null, $this->favicons->imageScale),
            'image/png',
            null
        );
        $icon310x310 = $this->processIcon(
            $asset,
            '/favicons/icon-310x310.{hash}.png',
            Configuration::create(310, 310, 'png', null, null, $this->favicons->imageScale),
            'image/png',
            null
        );
        $icon310x150 = $this->processIcon(
            $asset,
            '/favicons/icon-310x150.{hash}.png',
            Configuration::create(310, 150, 'png', null, null, $this->favicons->imageScale),
            'image/png',
            null
        );
        $icon144x144 = $this->processIcon(
            $asset,
            '/favicons/icon-144x144.{hash}.png',
            Configuration::create(144, 144, 'png', null, null, $this->favicons->imageScale),
            'image/png',
            null
        );

        if ($this->favicons->tileColor === null) {
            $tileColor = '';
        } else {
            $tileColor = PHP_EOL . sprintf('            <TileColor>%s</TileColor>', $this->favicons->tileColor);
        }

        $content = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
    <msapplication>
        <tile>
            <square70x70logo src="{$icon70x70->url}"/>
            <square150x150logo src="{$icon150x150->url}"/>
            <square310x310logo src="{$icon310x310->url}"/>
            <wide310x150logo src="{$icon310x150->url}"/>{$tileColor}
        </tile>
    </msapplication>
</browserconfig>
XML;
        $hash = $this->debug === true ? '' : hash('xxh128', $content);
        $url = sprintf('/favicons/browserconfig.%s.xml', $hash);
        $browserConfig = Data::create(
            $url,
            $content,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'application/xml',
                'X-Favicons-Dev' => true,
                'Etag' => $hash,
            ],
            sprintf('<meta name="msapplication-config" content="%s">', $url)
        );

        return [
            $icon70x70,
            $icon150x150,
            $icon310x310,
            $icon310x150,
            Data::create(
                $icon144x144->url,
                $icon144x144->data,
                $icon144x144->headers,
                sprintf('<meta name="msapplication-TileImage" content="%s">', $icon144x144->url)
            ),
            $browserConfig,
        ];
    }
}
