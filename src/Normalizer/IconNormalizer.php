<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Icon;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final readonly class IconNormalizer implements NormalizerInterface
{
    public function __construct(
        private AssetMapperInterface $assetMapper
    ) {
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        assert($object instanceof Icon);
        $url = null;
        if (! str_starts_with($object->src, '/')) {
            $url = $this->assetMapper->getAsset($object->src)?->publicPath;
        }
        if ($url === null) {
            $url = $object->src;
        }

        $result = [
            'src' => $url,
            'sizes' => $object->getSizeList(),
            'type' => $object->format,
            'purpose' => $object->purpose,
        ];

        $cleanup = static fn (array $data): array => array_filter(
            $data,
            static fn ($value) => ($value !== null && $value !== [])
        );

        return $cleanup($result);
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Icon;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Icon::class => true,
        ];
    }
}
