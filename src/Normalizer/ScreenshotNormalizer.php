<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Screenshot;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final readonly class ScreenshotNormalizer implements NormalizerInterface
{
    public function __construct(
        private AssetMapperInterface $assetMapper,
        private null|ImageProcessor $imageProcessor,
    ) {
    }

    /**
     * @return array{src: string, sizes?: string, form_factor?: string, label?: string, platform?: string, format?: string}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        assert($object instanceof Screenshot);
        $url = null;
        $asset = null;
        if (! str_starts_with($object->src, '/')) {
            $asset = $this->assetMapper->getAsset($object->src);
            $url = $asset?->publicPath;
        }
        if ($url === null) {
            $url = $object->src;
        }
        ['sizes' => $sizes, 'formFactor' => $formFactor] = $this->getSizes($object, $asset);
        $format = $this->getFormat($object, $asset);

        $result = [
            'src' => $url,
            'sizes' => $sizes,
            'form_factor' => $formFactor,
            'label' => $object->label,
            'platform' => $object->platform,
            'format' => $format,
        ];

        $cleanup = static fn (array $data): array => array_filter(
            $data,
            static fn ($value) => ($value !== null && $value !== [])
        );
        return $cleanup($result);
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Screenshot;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Screenshot::class => true,
        ];
    }

    /**
     * @return array{sizes: string|null, formFactor: string|null}
     */
    private function getSizes(Screenshot $object, null|MappedAsset $asset): array
    {
        if ($object->width !== null && $object->height !== null) {
            return [
                'sizes' => sprintf('%dx%d', $object->width, $object->height),
                'formFactor' => $object->formFactor ?? $this->getFormFactor($object->width, $object->height),
            ];
        }

        if ($this->imageProcessor === null || $asset === null) {
            return [
                'sizes' => null,
                'formFactor' => $object->formFactor,
            ];
        }

        ['width' => $width, 'height' => $height] = $this->imageProcessor->getSizes(
            file_get_contents($asset->sourcePath)
        );

        return [
            'sizes' => sprintf('%dx%d', $width, $height),
            'formFactor' => $object->formFactor ?? $this->getFormFactor($width, $height),
        ];
    }

    private function getFormat(Screenshot $object, ?MappedAsset $asset): ?string
    {
        if ($object->format !== null) {
            return $object->format;
        }

        if ($this->imageProcessor === null || $asset === null || ! class_exists(MimeTypes::class)) {
            return null;
        }

        $mime = MimeTypes::getDefault();
        return $mime->guessMimeType($asset->sourcePath);
    }

    private function getFormFactor(?int $width, ?int $height): ?string
    {
        if ($width === null || $height === null) {
            return null;
        }

        return $width > $height ? 'wide' : 'narrow';
    }
}
