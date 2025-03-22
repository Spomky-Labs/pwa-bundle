<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use function assert;
use function is_string;
use function sprintf;
use const PHP_EOL;

final class FaviconsCompiler implements FileCompilerInterface, CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly null|ImageProcessorInterface $imageProcessor,
        private readonly Favicons $favicons,
        private readonly AssetMapperInterface $assetMapper,
        #[Autowire(param: 'kernel.debug')]
        public readonly bool $debug,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @return iterable<string, Data>
     */
    public function getFiles(): iterable
    {
        $this->logger->debug('Compiling favicons.', [
            'favicons' => $this->favicons,
        ]);
        if ($this->imageProcessor === null || $this->favicons->enabled === false) {
            $this->logger->debug('Favicons are disabled or no image processor is available.');
            yield from [];

            return;
        }
        [$asset, $hash] = $this->getFavicon();
        assert($asset !== null, 'The asset does not exist.');
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
                'url' => '/pwa/favicon-%dx%d-%s.png',
                'width' => 16,
                'height' => 16,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            [
                'url' => '/pwa/favicon-%dx%d-%s.png',
                'width' => 32,
                'height' => 32,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            //High resolution iOS
            [
                'url' => '/pwa/favicon-%dx%d-%s.png',
                'width' => 180,
                'height' => 180,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'apple-touch-icon',
            ],
            //High resolution chrome
            [
                'url' => '/pwa/favicon-%dx%d-%s.png',
                'width' => 192,
                'height' => 192,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
            [
                'url' => '/pwa/favicon-%dx%d-%s.png',
                'width' => 512,
                'height' => 512,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'icon',
            ],
        ];

        if ($this->favicons->useStartImage === true) {
            $sizes = [
                ...$sizes,
                //Portrait
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 640,
                    'height' => 1136,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 40,
                    'media' => '(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 750,
                    'height' => 1294,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 40,
                    'media' => '(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 1242,
                    'height' => 2148,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 40,
                    'media' => '(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 1125,
                    'height' => 2436,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 40,
                    'media' => '(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 1536,
                    'height' => 2048,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 40,
                    'media' => '(min-device-width: 768px) and (max-device-width: 1024px) and (-webkit-min-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 1668,
                    'height' => 2224,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 40,
                    'media' => '(min-device-width: 834px) and (max-device-width: 834px) and (-webkit-min-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 2048,
                    'height' => 2732,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 40,
                    'media' => '(min-device-width: 1024px) and (max-device-width: 1024px) and (-webkit-min-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                //Landscape
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 1136,
                    'height' => 640,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 30,
                    'media' => '(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 1294,
                    'height' => 750,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 30,
                    'media' => '(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 2148,
                    'height' => 1242,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 30,
                    'media' => '(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 2436,
                    'height' => 1125,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 30,
                    'media' => '(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 2048,
                    'height' => 1536,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 30,
                    'media' => '(min-device-width: 768px) and (max-device-width: 1024px) and (-webkit-min-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 2224,
                    'height' => 1668,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 30,
                    'media' => '(min-device-width: 834px) and (max-device-width: 834px) and (-webkit-min-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => 2732,
                    'height' => 2048,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => 30,
                    'media' => '(min-device-width: 1024px) and (max-device-width: 1024px) and (-webkit-min-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
            ];
        }

        if ($this->favicons->lowResolution === true) {
            $sizes = [
                ...$sizes,
                //Prior iOS 6
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 57,
                    'height' => 57,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 72,
                    'height' => 72,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 114,
                    'height' => 114,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],

                //Prior iOS 7
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 60,
                    'height' => 60,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 76,
                    'height' => 76,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 120,
                    'height' => 120,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 152,
                    'height' => 152,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-icon',
                ],

                //Other resolution
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 36,
                    'height' => 36,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 48,
                    'height' => 48,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 72,
                    'height' => 72,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 96,
                    'height' => 96,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 144,
                    'height' => 144,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
                    'width' => 256,
                    'height' => 256,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'icon',
                ],
                [
                    'url' => '/pwa/favicon-%dx%d-%s.png',
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
                $size['imageScale'] ?? $this->favicons->imageScale,
                $this->favicons->monochrome
            );
            $completeHash = hash('xxh128', $hash . $configuration);
            $filename = sprintf($size['url'], $size['width'], $size['height'], $completeHash);
            yield $filename => $this->processIcon(
                $asset,
                $filename,
                $configuration,
                $size['mimetype'],
                $size['rel'],
                $size['media'] ?? null
            );
        }
        if ($this->favicons->tileColor !== null) {
            $this->logger->debug('Creating browserconfig.xml.');
            yield from $this->processBrowserConfig($asset, $hash);
        }
        if ($this->favicons->safariPinnedTabColor !== null && $this->favicons->useSilhouette === true) {
            $safariPinnedTab = $this->generateSafariPinnedTab($asset, $hash);
            yield $safariPinnedTab->url => $safariPinnedTab;
        }
        $this->logger->debug('Favicons created.');
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function processIcon(
        string $asset,
        string $publicUrl,
        Configuration $configuration,
        string $mimeType,
        null|string $rel,
        null|string $media = null,
    ): Data {
        $this->logger->debug('Processing icon.', [
            'publicUrl' => $publicUrl,
            'configuration' => $configuration,
            'mimeType' => $mimeType,
            'rel' => $rel,
            'media' => $media,
        ]);
        $closure = fn (): string => $this->imageProcessor->process($asset, null, null, null, $configuration);
        if ($this->debug === true) {
            $html = $rel === null ? null : sprintf(
                '<link rel="%s" sizes="%dx%d" type="%s" href="%s"%s>',
                $rel,
                $configuration->width,
                $configuration->height,
                $mimeType,
                $publicUrl,
                is_string($media) ? sprintf(' media="%s"', $media) : ''
            );
            return Data::create(
                $publicUrl,
                $closure,
                [
                    'Cache-Control' => 'public, max-age=604800, immutable',
                    'Content-Type' => $mimeType,
                    'X-Pwa-Dev' => true,
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
                'X-Pwa-Dev' => true,
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
        if ($this->favicons->useSilhouette === true && $this->debug === false) {
            $asset = $this->generateSilhouette($asset);
        }
        $this->logger->debug('Processing browserconfig.xml.');
        $configuration = Configuration::create(70, 70, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon70x70 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 70, 70, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(150, 150, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon150x150 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 150, 150, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(310, 310, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon310x310 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 310, 310, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(310, 150, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon310x150 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 310, 150, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(144, 144, 'png', null, null, $this->favicons->imageScale);
        $hash = hash('xxh128', $hash . $configuration);
        $icon144x144 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 144, 144, $hash),
            $configuration,
            'image/png',
            null
        );

        if ($this->favicons->tileColor === null) {
            $this->logger->debug('No tile color defined.');
            $tileColor = '';
        } else {
            $this->logger->debug('Tile color defined.');
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
        $url = sprintf('/pwa/browserconfig-%s.xml', $browserConfigHash);
        $browserConfig = Data::create(
            $url,
            $content,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'application/xml',
                'X-Pwa-Dev' => true,
                'Etag' => hash('xxh128', $content),
            ],
            sprintf('<meta name="msapplication-config" content="%s">', $url)
        );

        return [
            $icon70x70->url => $icon70x70,
            $icon150x150->url => $icon150x150,
            $icon310x310->url => $icon310x310,
            $icon310x150->url => $icon310x150,
            $icon144x144->url => Data::create(
                $icon144x144->url,
                $icon144x144->getRawData(),
                $icon144x144->headers,
                sprintf('<meta name="msapplication-TileImage" content="%s">', $icon144x144->url)
            ),
            $browserConfig->url => $browserConfig,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getFavicon(): array
    {
        $source = $this->favicons->src;
        $this->logger->debug('Favicons are enabled. Trying to get the asset.', [
            'src' => $source,
        ]);
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

    private function generateSafariPinnedTab(string $content, string $hash): Data
    {
        $callback = fn (): string => $this->generateSilhouette($content);
        $url = sprintf('/pwa/safari-pinned-tab-%s.svg', $hash);

        return Data::create(
            $url,
            $callback,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'image/svg+xml',
                'X-Pwa-Dev' => true,
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
