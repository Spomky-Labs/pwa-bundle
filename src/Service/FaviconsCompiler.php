<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\UX\Icons\IconRendererInterface;
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
        private readonly ?IconRendererInterface $renderer,
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
            return [];
        }

        $faviconSources = [
            'light' => [
                'asset' => $this->favicons->default->src,
                'media' => null,
            ],
        ];

        if ($this->favicons->dark !== null) {
            $faviconSources['dark'] = [
                'asset' => $this->favicons->dark->src,
                'media' => '(prefers-color-scheme: dark)',
            ];
        }

        $sizes = $this->getFaviconSizes();

        foreach ($faviconSources as $mode => $sourceInfo) {
            $theme = $mode === 'light' ? $this->favicons->default : $this->favicons->dark;
            if ($theme === null) {
                continue;
            }
            $asset = $this->getFaviconAsset($sourceInfo['asset'], $theme->svgAttributes);
            $hash = hash('xxh128', $asset);

            foreach ($sizes as $size) {
                $configuration = Configuration::create(
                    $size['width'],
                    $size['height'],
                    $size['format'],
                    $theme->backgroundColor,
                    $theme->borderRadius,
                    $size['imageScale'] ?? $theme->imageScale,
                    $this->favicons->monochrome
                );
                $completeHash = hash('xxh128', $hash . $configuration);
                $filename = sprintf($size['url'], $size['width'], $size['height'], $completeHash);
                $media = $size['media'] ?? null;
                if ($this->favicons->dark !== null) {
                    if ($media !== null) {
                        $media = sprintf(
                            '(%s) and %s',
                            $media,
                            $sourceInfo['media'] ?? ($this->favicons->dark->src !== null ? '(prefers-color-scheme: light)' : null)
                        );
                    } else {
                        $media = $sourceInfo['media'] ?? ($this->favicons->dark->src !== null ? '(prefers-color-scheme: light)' : null);
                    }
                }

                yield $filename => $this->processIcon(
                    $asset,
                    $filename,
                    $configuration,
                    $size['mimetype'],
                    $size['rel'],
                    $media
                );
            }
        }

        if ($this->favicons->tileColor !== null) {
            $this->logger->debug('Creating browserconfig.xml.');
            $image = $this->getFaviconAsset($this->favicons->default->src, $this->favicons->default->svgAttributes);
            yield from $this->processBrowserConfig($image, hash('xxh128', $image));
        }

        if ($this->favicons->safariPinnedTabColor !== null && $this->favicons->useSilhouette === true) {
            $image = $this->getFaviconAsset($this->favicons->default->src, $this->favicons->default->svgAttributes);
            $hash = hash('xxh128', $image);
            $safariPinnedTab = $this->generateSafariPinnedTab($image, $hash);
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

        $mediaAttr = is_string($media) ? sprintf(' media="%s"', $media) : '';
        $html = $rel === null ? null : sprintf(
            '<link rel="%s" sizes="%dx%d" type="%s" href="%s"%s>',
            $rel,
            $configuration->width,
            $configuration->height,
            $mimeType,
            $publicUrl,
            $mediaAttr
        );

        $headers = [];
        if ($this->debug) {
            $headers = [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => $mimeType,
                'X-Pwa-Dev' => true,
            ];
        }

        return Data::create(
            $publicUrl,
            $closure,
            $headers,
            $html
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
        $configuration = Configuration::create(
            70,
            70,
            'png',
            null,
            null,
            $this->favicons->default->imageScale,
            false,
            $this->favicons->default->svgAttributes
        );
        $hash = hash('xxh128', $hash . $configuration);
        $icon70x70 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 70, 70, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(
            150,
            150,
            'png',
            null,
            null,
            $this->favicons->default->imageScale,
            false,
            $this->favicons->default->svgAttributes
        );
        $hash = hash('xxh128', $hash . $configuration);
        $icon150x150 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 150, 150, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(
            310,
            310,
            'png',
            null,
            null,
            $this->favicons->default->imageScale,
            false,
            $this->favicons->default->svgAttributes
        );
        $hash = hash('xxh128', $hash . $configuration);
        $icon310x310 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 310, 310, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(
            310,
            150,
            'png',
            null,
            null,
            $this->favicons->default->imageScale,
            false,
            $this->favicons->default->svgAttributes
        );
        $hash = hash('xxh128', $hash . $configuration);
        $icon310x150 = $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', 310, 150, $hash),
            $configuration,
            'image/png',
            null
        );

        $configuration = Configuration::create(
            144,
            144,
            'png',
            null,
            null,
            $this->favicons->default->imageScale,
            false,
            $this->favicons->default->svgAttributes
        );
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

        $headersImg = $this->debug ? [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Content-Type' => 'image/png',
            'X-Pwa-Dev' => true,
        ] : [];

        $headersXml = $this->debug ? [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Content-Type' => 'application/xml',
            'X-Pwa-Dev' => true,
            'Etag' => hash('xxh128', $content),
        ] : [];

        $browserConfig = Data::create(
            $url,
            $content,
            $headersXml,
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
                $headersImg,
                sprintf('<meta name="msapplication-TileImage" content="%s">', $icon144x144->url)
            ),
            $browserConfig->url => $browserConfig,
        ];
    }

    private function generateSafariPinnedTab(string $content, string $hash): Data
    {
        $callback = fn (): string => $this->generateSilhouette($content);
        $url = sprintf('/pwa/safari-pinned-tab-%s.svg', $hash);

        $headers = $this->debug ? [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Content-Type' => 'image/svg+xml',
            'X-Pwa-Dev' => true,
            'Etag' => $hash,
        ] : [];

        return Data::create(
            $url,
            $callback,
            $headers,
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
            'white',
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

    /**
     * @param array<string, mixed> $attributes
     */
    private function getFaviconAsset(Asset $asset, array $attributes): string
    {
        if (str_starts_with($asset->src, '/')) {
            return (new Filesystem())->readFile($asset->src);
        }
        if ($this->renderer !== null && str_contains($asset->src, ':')) {
            return $this->renderer->renderIcon($asset->src, $attributes);
        }
        $mappedAsset = $this->assetMapper->getAsset($asset->src);
        assert($mappedAsset, sprintf('Invalid asset "%s"', $asset->src));
        assert($mappedAsset instanceof MappedAsset, sprintf('Invalid asset "%s"', $mappedAsset->sourcePath));

        $content = $mappedAsset->content;
        if ($content === null) {
            $content = (new Filesystem())->readFile($mappedAsset->sourcePath);
        }

        return $content;
    }

    /**
     * @return array{url: string, width: int, height: int, format: string, mimetype: string, rel: string}[]
     */
    private function getFaviconSizes(): array
    {
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

            $startupImages = [
                [
                    'height' => 2048,
                    'width' => 2732,
                    'media' => '(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 2732,
                    'width' => 2048,
                    'media' => '(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 1668,
                    'width' => 2388,
                    'media' => '(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 2388,
                    'width' => 1668,
                    'media' => '(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 1536,
                    'width' => 2048,
                    'media' => '(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 2048,
                    'width' => 1536,
                    'media' => '(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 1640,
                    'width' => 2360,
                    'media' => '(device-width: 820px) and (device-height: 1180px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 2360,
                    'width' => 1640,
                    'media' => '(device-width: 820px) and (device-height: 1180px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 1668,
                    'width' => 2224,
                    'media' => '(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 2224,
                    'width' => 1668,
                    'media' => '(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 1620,
                    'width' => 2160,
                    'media' => '(device-width: 810px) and (device-height: 1080px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 2160,
                    'width' => 1620,
                    'media' => '(device-width: 810px) and (device-height: 1080px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 1488,
                    'width' => 2266,
                    'media' => '(device-width: 744px) and (device-height: 1133px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 2266,
                    'width' => 1488,
                    'media' => '(device-width: 744px) and (device-height: 1133px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 1320,
                    'width' => 2868,
                    'media' => '(device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2868,
                    'width' => 1320,
                    'media' => '(device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 1206,
                    'width' => 2622,
                    'media' => '(device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2622,
                    'width' => 1206,
                    'media' => '(device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 1290,
                    'width' => 2796,
                    'media' => '(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2796,
                    'width' => 1290,
                    'media' => '(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 1179,
                    'width' => 2556,
                    'media' => '(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2556,
                    'width' => 1179,
                    'media' => '(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 1170,
                    'width' => 2532,
                    'media' => '(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2532,
                    'width' => 1170,
                    'media' => '(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 1284,
                    'width' => 2778,
                    'media' => '(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2778,
                    'width' => 1284,
                    'media' => '(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 1125,
                    'width' => 2436,
                    'media' => '(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2436,
                    'width' => 1125,
                    'media' => '(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 1242,
                    'width' => 2688,
                    'media' => '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2688,
                    'width' => 1242,
                    'media' => '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 828,
                    'width' => 1792,
                    'media' => '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 1792,
                    'width' => 828,
                    'media' => '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 1242,
                    'width' => 2208,
                    'media' => '(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
                ],
                [
                    'height' => 2208,
                    'width' => 1242,
                    'media' => '(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
                ],
                [
                    'height' => 750,
                    'width' => 1334,
                    'media' => '(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 1334,
                    'width' => 750,
                    'media' => '(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
                [
                    'height' => 640,
                    'width' => 1136,
                    'media' => '(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
                ],
                [
                    'height' => 1136,
                    'width' => 640,
                    'media' => '(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
                ],
            ];
            foreach ($startupImages as $startupImage) {
                [$height, $width, $media] = array_values($startupImage);
                $diagonal = sqrt($width ** 2 + $height ** 2);
                $scale = 30 + 10 * exp(-$diagonal / 1500);
                $sizes[] = [
                    'url' => '/pwa/start-image-%dx%d-%s.png',
                    'width' => $width,
                    'height' => $height,
                    'format' => 'png',
                    'mimetype' => 'image/png',
                    'rel' => 'apple-touch-startup-image',
                    'imageScale' => (int) $scale,
                    'media' => $media,
                ];
            }
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

        return $sizes;
    }
}
