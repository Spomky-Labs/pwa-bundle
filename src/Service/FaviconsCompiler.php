<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Favicons;
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
        $this->files = [
            '/favicon.ico' => $this->processIcon($asset, '/favicon.ico', 16, 16, 'ico', 'image/x-icon'),
        ];
        $sizes = [16, 32, 36, 48, 57, 60, 70, 72, 76, 96, 114, 120, 144, 150, 152, 180, 192, 194, 256, 310, 384, 512];
        foreach ($sizes as $size) {
            $this->files[sprintf('/favicons/icon-%dx%d.png', $size, $size)] = $this->processIcon(
                $asset,
                sprintf('/favicons/icon-%dx%d.{hash}.png', $size, $size),
                $size,
                $size,
                'png',
                'image/png'
            );
        }
        if ($this->favicons->tileColor !== null) {
            $this->files['/favicons/icon-310x150.png'] = $this->processIcon(
                $asset,
                '/favicons/icon-310x150.{hash}.png',
                310,
                150,
                'png',
                'image/png'
            );
            $this->files['/favicons/browserconfig.xml'] = $this->processBrowserConfig();
        }

        return $this->files;
    }

    private function processIcon(
        MappedAsset $asset,
        string $publicUrl,
        int $width,
        int $height,
        string $format,
        string $mimeType
    ): Data {
        $content = file_get_contents($asset->sourcePath);
        assert($content !== false);
        if ($this->debug === true) {
            $hash = hash('xxh128', $content);
            return Data::create(
                str_replace(['{hash}', '.png'], [$hash, '.svg'], $publicUrl),
                $content,
                [
                    'Cache-Control' => 'public, max-age=604800, immutable',
                    'Content-Type' => 'image/svg+xml',
                    'X-Favicons-Dev' => true,
                    'Etag' => $hash,
                ]
            );
        }
        assert($this->imageProcessor !== null);
        $data = $this->imageProcessor->process($content, $width, $height, $format);
        return Data::create(
            str_replace('{hash}', hash('xxh128', $data), $publicUrl),
            $data,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => $mimeType,
                'X-Favicons-Dev' => true,
                'Etag' => hash('xxh128', $data),
            ]
        );
    }

    private function processBrowserConfig(): Data
    {
        $icon310x150 = $this->files['/favicons/icon-310x150.png'] ?? null;
        $icon70x70 = $this->files['/favicons/icon-70x70.png'] ?? null;
        $icon150x150 = $this->files['/favicons/icon-150x150.png'] ?? null;
        $icon310x310 = $this->files['/favicons/icon-310x310.png'] ?? null;
        assert($icon310x150 !== null);
        assert($icon70x70 !== null);
        assert($icon150x150 !== null);
        assert($icon310x310 !== null);
        if ($this->favicons->tileColor === null) {
            $tileColor = '';
        } else {
            $tileColor = sprintf(PHP_EOL . '            <TileColor>%s</TileColor>', $this->favicons->tileColor);
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
        $hash = hash('xxh128', $content);
        return Data::create(
            sprintf('/favicons/browserconfig.%s.xml', $hash),
            $content,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'application/xml',
                'X-Favicons-Dev' => true,
                'Etag' => $hash,
            ]
        );
    }
}
