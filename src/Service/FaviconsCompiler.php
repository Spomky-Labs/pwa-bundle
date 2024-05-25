<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use RuntimeException;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
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
        [$asset, $hash] = $this->getFavicon();
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
                'url' => '/favicons/icon-%dx%d-%s.png',
                'width' => 16,
                'height' => 16,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            [
                'url' => '/favicons/icon-%dx%d-%s.png',
                'width' => 32,
                'height' => 32,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            //High resolution iOS
            [
                'url' => '/favicons/icon-%dx%d-%s.png',
                'width' => 180,
                'height' => 180,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'apple-touch-icon',
            ],
            //High resolution chrome
            [
                'url' => '/favicons/icon-%dx%d-%s.png',
                'width' => 192,
                'height' => 192,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            [
                'url' => '/favicons/icon-%dx%d-%s.png',
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
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 57,
                    'height' => 57,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 72,
                    'height' => 72,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 114,
                    'height' => 114,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],

                //Prior iOS 7
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 60,
                    'height' => 60,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 76,
                    'height' => 76,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 120,
                    'height' => 120,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 152,
                    'height' => 152,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],

                //Other resolution
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 36,
                    'height' => 36,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 48,
                    'height' => 48,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 72,
                    'height' => 72,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 96,
                    'height' => 96,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 144,
                    'height' => 144,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
                    'width' => 256,
                    'height' => 256,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/favicons/icon-%dx%d-%s.png',
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
            $completeHash = hash('xxh128', $hash . $configuration);
            $filename = sprintf($size['url'], $size['width'], $size['height'], $completeHash);
            $this->files[$filename] = $this->processIcon(
                $asset,
                $filename,
                $configuration,
                $size['mimetype'],
                $size['rel'],
            );
        }
        if ($this->favicons->tileColor !== null) {
            $this->files = [...$this->files, ...$this->processBrowserConfig($asset, $hash)];
        }
        if ($this->favicons->safariPinnedTabColor !== null && $this->favicons->useSilhouette === true) {
            $safariPinnedTab = $this->generateSafariPinnedTab($asset);
            $this->files[$safariPinnedTab->url] = $safariPinnedTab;
        }

        return $this->files;
    }

    private function processIcon(
        string $asset,
        string $publicUrl,
        Configuration $configuration,
        string $mimeType,
        null|string $rel,
    ): Data {
        $closure = fn (): string => $this->imageProcessor->process($asset, null, null, null, $configuration);
        if ($this->debug === true) {
            $html = $rel === null ? null : sprintf(
                '<link rel="%s" sizes="%dx%d" type="%s" href="%s">',
                $rel,
                $configuration->width,
                $configuration->height,
                $mimeType,
                $publicUrl
            );
            return Data::create(
                $publicUrl,
                $closure,
                [
                    'Cache-Control' => 'public, max-age=604800, immutable',
                    'Content-Type' => $mimeType,
                    'X-Favicons-Dev' => true,
                ],
                $html
            );
        }
        assert($this->imageProcessor !== null);
        return Data::create(
            $publicUrl,
            $closure,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => $mimeType,
                'X-Favicons-Dev' => true,
            ],
            sprintf(
                '<link rel="%s" sizes="%dx%d" type="%s" href="%s">',
                $rel,
                $configuration->width,
                $configuration->height,
                $mimeType,
                $publicUrl
            )
        );
    }

    /**
     * @return array<Data>
     */
    private function processBrowserConfig(string $asset, string $hash): array
    {
        if ($this->favicons->useSilhouette === true) {
            $asset = $this->generateSilhouette($asset);
        }
        $configuration = Configuration::create(70, 70, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon70x70 = $this->processIcon(
            $asset,
            sprintf('/favicons/icon-%dx%d-%s.png', 70, 70, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(150, 150, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon150x150 = $this->processIcon(
            $asset,
            sprintf('/favicons/icon-%dx%d-%s.png', 150, 150, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(310, 310, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon310x310 = $this->processIcon(
            $asset,
            sprintf('/favicons/icon-%dx%d-%s.png', 310, 310, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(310, 150, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon310x150 = $this->processIcon(
            $asset,
            sprintf('/favicons/icon-%dx%d-%s.png', 310, 150, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(144, 144, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon144x144 = $this->processIcon(
            $asset,
            sprintf('/favicons/icon-%dx%d-%s.png', 144, 144, $hash),
            $configuration,
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
        $browserConfigHash = hash('xxh128', $content);
        $url = sprintf('/favicons/browserconfig-%s.xml', $browserConfigHash);
        $browserConfig = Data::create(
            $url,
            $content,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'application/xml',
                'X-Favicons-Dev' => true,
                'Etag' => hash('xxh128', $content),
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
                $icon144x144->getRawData(),
                $icon144x144->headers,
                sprintf('<meta name="msapplication-TileImage" content="%s">', $icon144x144->url)
            ),
            $browserConfig,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getFavicon(): array
    {
        $source = $this->favicons->src;
        if (! str_starts_with($source->src, '/')) {
            $asset = $this->assetMapper->getAsset($source->src);
            assert($asset !== null, 'Unable to find the favicon source asset');
            return [$asset->content ?? file_get_contents($asset->sourcePath), $asset->digest];
        }
        assert(file_exists($source->src), 'Unable to find the favicon source file');
        $data = file_get_contents($source->src);
        assert($data !== false, 'Unable to read the favicon source file');
        $hash = hash('xxh128', $data);

        return [$data, $hash];
    }

    private function generateSafariPinnedTab(string $content): Data
    {
        $silhouette = $this->generateSilhouette($content);
        $hash = hash('xxh128', $silhouette);
        $url = sprintf('/safari-pinned-tab-%s.svg', $hash);

        return Data::create(
            $url,
            $silhouette,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'image/svg+xml',
                'X-Favicons-Dev' => true,
                'Etag' => $hash,
            ],
            sprintf('<link rel="mask-icon" href="%s" color="%s">', $url, $this->favicons->safariPinnedTabColor)
        );
    }

    private function generateSilhouette(string $asset): string
    {
        assert($this->imageProcessor !== null);
        $bmp = $this->imageProcessor->process($asset, null, null, null, configuration: Configuration::create(
            512,
            512,
            'bmp',
            'white'
        ));
        $tempFile = tempnam(sys_get_temp_dir(), 'safari-pinned-tab');
        assert($tempFile !== false, 'Unable to create a temporary file');
        file_put_contents($tempFile, $bmp);
        $tempOutput = tempnam(sys_get_temp_dir(), 'safari-pinned-tab');
        assert($tempOutput !== false, 'Unable to create a temporary file');

        $command = [
            $this->favicons->potrace,
            '--alphamax', '0',
            '--opttolerance', '0',
            '--turdsize', '0',
            '--flat',
            '--color', '#ffffff',
            '--svg',
            '-o',
            $tempOutput,
            $tempFile,
        ];

        $process = new Process($command);

        try {
            $result = $process->run();
            if ($result !== 0) {
                throw new RuntimeException('Unable to run potrace. Error: ' . $process->getErrorOutput());
            }
            $process->wait();
        } catch (ProcessFailedException $exception) {
            throw new RuntimeException('Unable to generate the Safari pinned tab icon.', 0, $exception);
        }
        $svg = file_get_contents($tempOutput);
        assert($svg !== false, 'Unable to read the SVG file');
        unlink($tempFile);
        unlink($tempOutput);

        return $svg;
    }
}
