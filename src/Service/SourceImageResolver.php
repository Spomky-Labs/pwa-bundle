<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function is_string;
use const PATHINFO_EXTENSION;
use RuntimeException;
use SpomkyLabs\PwaBundle\Dto\Asset;
use function sprintf;
use function str_starts_with;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Mime\MimeTypes;
use Symfony\UX\Icons\IconRendererInterface;

/**
 * Reads a configured image source, whatever it is declared as: an Asset Mapper logical path, an absolute
 * path, or a Symfony UX Icon name.
 */
final readonly class SourceImageResolver
{
    /**
     * @var array<string, string>
     */
    private const MAGIC_NUMBERS = [
        "\x89PNG\r\n\x1a\n" => 'image/png',
        "\xff\xd8\xff" => 'image/jpeg',
        'GIF87a' => 'image/gif',
        'GIF89a' => 'image/gif',
    ];

    public function __construct(
        private AssetMapperInterface $assetMapper,
        private null|IconRendererInterface $iconRenderer,
    ) {
    }

    /**
     * @param array<string, bool|string> $svgAttributes
     */
    public function resolve(Asset $asset, array $svgAttributes = []): SourceImage
    {
        $content = $this->getContent($asset, $svgAttributes);

        return SourceImage::create($content, $this->detectMimeType($asset->src, $content));
    }

    /**
     * @param array<string, bool|string> $svgAttributes
     */
    private function getContent(Asset $asset, array $svgAttributes): string
    {
        if (str_starts_with($asset->src, '/')) {
            return $this->read($asset->src);
        }
        if ($this->iconRenderer !== null && str_contains($asset->src, ':')) {
            return $this->iconRenderer->renderIcon($asset->src, $svgAttributes);
        }

        // Guarded rather than asserted: assertions are compiled out in production, where a missing asset
        // used to surface as a property read on null from inside this method.
        $mappedAsset = $this->assetMapper->getAsset($asset->src);
        if (! $mappedAsset instanceof MappedAsset) {
            throw new RuntimeException(sprintf('The image source "%s" cannot be found.', $asset->src));
        }

        return $mappedAsset->content ?? $this->read($mappedAsset->sourcePath);
    }

    private function read(string $path): string
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException(sprintf('The image source "%s" cannot be read.', $path));
        }

        return $content;
    }

    /**
     * The magic numbers are looked at before the file name: a UX Icon has no extension at all, and an
     * Asset Mapper path can very well point at a ".svg" holding something else after a build step.
     */
    private function detectMimeType(string $src, string $content): string
    {
        foreach (self::MAGIC_NUMBERS as $signature => $mimeType) {
            if (str_starts_with($content, $signature)) {
                return $mimeType;
            }
        }
        if (str_starts_with($content, 'RIFF') && str_contains(substr($content, 0, 16), 'WEBP')) {
            return 'image/webp';
        }
        if (str_contains(substr($content, 0, 1024), '<svg')) {
            return 'image/svg+xml';
        }

        return $this->guessFromExtension($src) ?? 'application/octet-stream';
    }

    private function guessFromExtension(string $src): null|string
    {
        if (! class_exists(MimeTypes::class)) {
            return null;
        }
        $extension = pathinfo($src, PATHINFO_EXTENSION);
        if (! is_string($extension) || $extension === '') {
            return null;
        }

        return MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? null;
    }
}
