<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use function assert;
use SpomkyLabs\PwaBundle\Dto\Screenshot;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use function sprintf;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ScreenshotNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(
        private readonly AssetMapperInterface $assetMapper,
        private readonly null|ImageProcessorInterface $imageProcessor,
    ) {
    }

    /**
     * @return array{src: string, sizes?: string, form_factor?: string, label?: string, platform?: string, type?: string}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof Screenshot);
        $asset = null;
        $imageType = $data->type;
        if ($imageType === null && ! str_starts_with($data->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($data->src->src);
            $imageType = $this->getType($asset);
        }
        ['sizes' => $sizes, 'formFactor' => $formFactor] = $this->getSizes($data, $asset);

        $result = [
            'src' => $this->normalizer->normalize($data->src, $format, $context),
            'sizes' => $sizes,
            'form_factor' => $formFactor,
            'label' => $this->normalizer->normalize($data->getLabel(), $format, $context),
            'platform' => $data->platform,
            'type' => $imageType,
        ];

        $cleanup = static fn (array $data): array => array_filter(
            $data,
            static fn ($value) => ($value !== null && $value !== [])
        );
        return $cleanup($result);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
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
                'formFactor' => $object->formFactor,
            ];
        }

        if ($this->imageProcessor === null || $asset === null) {
            return [
                'sizes' => null,
                'formFactor' => $object->formFactor,
            ];
        }

        $imageData = @file_get_contents($asset->sourcePath);
        if ($imageData === false) {
            return [
                'sizes' => null,
                'formFactor' => $object->formFactor,
            ];
        }

        ['width' => $width, 'height' => $height] = $this->imageProcessor->getSizes($imageData);

        return [
            'sizes' => sprintf('%dx%d', $width, $height),
            'formFactor' => $object->formFactor,
        ];
    }

    private function getType(?MappedAsset $asset): ?string
    {
        if ($this->imageProcessor === null || $asset === null || ! class_exists(MimeTypes::class)) {
            return null;
        }

        return MimeTypes::getDefault()->guessMimeType($asset->sourcePath);
    }
}
