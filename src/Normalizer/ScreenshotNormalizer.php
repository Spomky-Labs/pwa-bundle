<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Screenshot;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final readonly class ScreenshotNormalizer implements NormalizerInterface
{
    public function __construct(
        private AssetMapperInterface $assetMapper
    ) {
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        assert($object instanceof Screenshot);
        $url = null;
        if (! str_starts_with($object->src, '/')) {
            $url = $this->assetMapper->getAsset($object->src)?->publicPath;
        }
        if ($url === null) {
            $url = $object->src;
        }
        $sizes = null;
        if ($object->width !== null && $object->height !== null) {
            $sizes = sprintf('%dx%d', $object->width, $object->height);
        }

        $result = [
            'src' => $url,
            'sizes' => $sizes,
            'width' => $object->width,
            'form_factor' => $object->formFactor,
            'label' => $object->label,
            'platform' => $object->platform,
            'format' => $object->format,
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
}
