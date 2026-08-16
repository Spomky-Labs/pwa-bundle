<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function assert;
use function is_string;
use const PHP_EOL;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use function sprintf;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class FaviconsCompiler implements FileCompilerInterface, CanLogInterface
{
    private LoggerInterface $logger;

    private readonly Favicons $favicons;

    public function __construct(
        private readonly null|ImageProcessorInterface $imageProcessor,
        FaviconsBuilder $faviconsBuilder,
        private readonly SourceImageResolver $sourceImageResolver,
        private readonly BasePathResolver $basePathResolver,
        #[Autowire(param: 'kernel.debug')]
        public readonly bool $debug,
    ) {
        $this->favicons = $faviconsBuilder->create();
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

            /** @var array<string, array{configuration: Configuration, mimetype: string, links: list<array{rel: string, media: null|string}>}> $icons */
            $icons = [];
            foreach ($sizes as $size) {
                // A fixed URL carries no content hash, so it cannot be declined per color scheme: the dark
                // variant would be written over the light one under the very same name, and both links would
                // end up pointing at whichever was generated last. Such an entry is emitted once, from the
                // default theme, and stays free of any color scheme condition.
                $hasFixedUrl = $size['fixedUrl'] ?? false;
                if ($hasFixedUrl && $mode !== 'light') {
                    continue;
                }

                $configuration = Configuration::create(
                    $size['width'],
                    $size['height'],
                    $size['format'],
                    $theme->backgroundColor,
                    $theme->borderRadius,
                    $theme->imageScale,
                    $this->favicons->monochrome
                );
                $completeHash = hash('xxh128', $hash . $configuration);
                $filename = sprintf($size['url'], $size['width'], $size['height'], $completeHash);
                $media = $hasFixedUrl ? null : $this->getColorSchemeMediaQuery($sourceInfo['media']);

                // Two sizes can describe the very same file: the low resolution block declares 72x72 both
                // as an apple-touch-icon and as an icon, for one identical configuration. Both links are
                // wanted, the second copy of the bytes is not, so the links are gathered per file name.
                $icons[$filename] ??= [
                    'configuration' => $configuration,
                    'mimetype' => $size['mimetype'],
                    'links' => [],
                ];
                $icons[$filename]['links'][] = [
                    'rel' => $size['rel'],
                    'media' => $media,
                ];
            }

            foreach ($icons as $filename => $icon) {
                yield $filename => $this->processIcon(
                    $asset,
                    $filename,
                    $icon['configuration'],
                    $icon['mimetype'],
                    $icon['links']
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

    /**
     * The condition an icon is declined under, which is the color scheme it is generated for, and nothing
     * else as long as no dark theme is configured.
     */
    private function getColorSchemeMediaQuery(null|string $schemeMedia): null|string
    {
        if ($this->favicons->dark === null) {
            return null;
        }

        // The default theme is the light one as soon as a dark theme is defined.
        return $schemeMedia ?? '(prefers-color-scheme: light)';
    }

    /**
     * @param list<array{rel: string, media: null|string}> $links
     */
    private function processIcon(
        string $asset,
        string $publicUrl,
        Configuration $configuration,
        string $mimeType,
        array $links = [],
    ): Data {
        $this->logger->debug('Processing icon.', [
            'publicUrl' => $publicUrl,
            'configuration' => $configuration,
            'mimeType' => $mimeType,
            'links' => $links,
        ]);
        $imageProcessor = $this->imageProcessor;
        if ($imageProcessor === null) {
            throw new RuntimeException('The image processor is not available.');
        }
        $closure = static fn (): string => $imageProcessor->process($asset, null, null, null, $configuration);

        $html = $links === [] ? null : implode(PHP_EOL, array_map(
            fn (array $link): string => sprintf(
                '<link rel="%s" sizes="%dx%d" type="%s" href="%s"%s>',
                $link['rel'],
                $configuration->width,
                $configuration->height,
                $mimeType,
                $this->basePathResolver->prefix($publicUrl),
                is_string($link['media']) ? sprintf(' media="%s"', $link['media']) : ''
            ),
            $links
        ));

        $headers = [];
        if ($this->debug) {
            $headers = [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => $mimeType,
                'X-Pwa-Dev' => true,
            ];
        }

        return Data::create($publicUrl, $closure, $headers, $html);
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

        $icon70x70 = $this->createTile($asset, 70, 70, $hash);
        $icon150x150 = $this->createTile($asset, 150, 150, $hash);
        $icon310x310 = $this->createTile($asset, 310, 310, $hash);
        $icon310x150 = $this->createTile($asset, 310, 150, $hash);
        $icon144x144 = $this->createTile($asset, 144, 144, $hash);

        if ($this->favicons->tileColor === null) {
            $this->logger->debug('No tile color defined.');
            $tileColor = '';
        } else {
            $this->logger->debug('Tile color defined.');
            $tileColor = PHP_EOL . sprintf('            <TileColor>%s</TileColor>', $this->favicons->tileColor);
        }

        // The hash is computed on the base path free version so that the compiled file keeps the same name
        // whatever the base path the application is served from.
        $browserConfigHash = hash('xxh128', $this->renderBrowserConfig(
            $icon70x70->url,
            $icon150x150->url,
            $icon310x310->url,
            $icon310x150->url,
            $tileColor
        ));
        $url = sprintf('/pwa/browserconfig-%s.xml', $browserConfigHash);
        $content = $this->renderBrowserConfig(
            $this->basePathResolver->prefix($icon70x70->url),
            $this->basePathResolver->prefix($icon150x150->url),
            $this->basePathResolver->prefix($icon310x310->url),
            $this->basePathResolver->prefix($icon310x150->url),
            $tileColor
        );

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
            sprintf('<meta name="msapplication-config" content="%s">', $this->basePathResolver->prefix($url))
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
                sprintf(
                    '<meta name="msapplication-TileImage" content="%s">',
                    $this->basePathResolver->prefix($icon144x144->url)
                )
            ),
            $browserConfig->url => $browserConfig,
        ];
    }

    /**
     * Builds one browserconfig tile, hashed from the asset and from its own configuration alone.
     *
     * The five hashes used to be chained, each one folding the previous, so inserting or removing a tile
     * renamed every file declared after it.
     *
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private function createTile(string $asset, int $width, int $height, string $hash): Data
    {
        $configuration = Configuration::create(
            $width,
            $height,
            'png',
            null,
            null,
            $this->favicons->default->imageScale,
            false,
            $this->favicons->default->svgAttributes
        );

        return $this->processIcon(
            $asset,
            sprintf('/pwa/favicon-%dx%d-%s.png', $width, $height, hash('xxh128', $hash . $configuration)),
            $configuration,
            'image/png'
        );
    }

    private function renderBrowserConfig(
        string $square70x70logo,
        string $square150x150logo,
        string $square310x310logo,
        string $wide310x150logo,
        string $tileColor,
    ): string {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
    <msapplication>
        <tile>
            <square70x70logo src="{$square70x70logo}"/>
            <square150x150logo src="{$square150x150logo}"/>
            <square310x310logo src="{$square310x310logo}"/>
            <wide310x150logo src="{$wide310x150logo}"/>{$tileColor}
        </tile>
    </msapplication>
</browserconfig>
XML;
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
            sprintf(
                '<link rel="mask-icon" href="%s" color="%s">',
                $this->basePathResolver->prefix($url),
                $this->favicons->safariPinnedTabColor
            )
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

        $potrace = $this->favicons->potrace;
        if ($potrace === null) {
            throw new RuntimeException(
                'Unable to generate the silhouette: no potrace binary configured. Set "pwa.favicons.potrace" or disable "use_silhouette".'
            );
        }

        $command = [
            $potrace,
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
     * @param array<string, bool|string> $attributes
     */
    private function getFaviconAsset(Asset $asset, array $attributes): string
    {
        return $this->sourceImageResolver->resolve($asset, $attributes)
            ->content;
    }

    /**
     * @return array{url: string, width: int<1, max>, height: int<1, max>, format: string, mimetype: string, rel: string, fixedUrl?: bool}[]
     */
    private function getFaviconSizes(): array
    {
        $sizes = [
            // Always
            [
                'url' => '/favicon.ico',
                'width' => 16,
                'height' => 16,
                'format' => 'ico',
                'mimetype' => 'image/x-icon',
                'rel' => 'icon',
                // Browsers fetch this URL by convention, with or without a link tag: it has to stay at that
                // exact path, which rules out both a content hash and a per-color-scheme variant.
                'fixedUrl' => true,
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
            // High resolution iOS
            [
                'url' => '/pwa/favicon-%dx%d-%s.png',
                'width' => 180,
                'height' => 180,
                'format' => 'png',
                'mimetype' => 'image/png',
                'rel' => 'apple-touch-icon',
            ],
            // High resolution chrome
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

        if ($this->favicons->lowResolution === true) {
            $sizes = [
                ...$sizes,
                // Prior iOS 6
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

                // Prior iOS 7
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

                // Other resolution
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
