<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Icon;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final class IconNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(
        private readonly AssetMapperInterface $assetMapper,
    ) {
    }

    /**
     * @return array{src: string, sizes?: string, type?: string, purpose?: string}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        assert($object instanceof Icon);
        $imageType = $object->type;
        if (! str_starts_with($object->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($object->src->src);
            $imageType = $this->getType($asset);
        }

        $result = [
            'src' => $this->normalizer->normalize($object->src, $format, $context),
            'sizes' => $object->getSizeList(),
            'type' => $imageType,
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

    private function getType(?MappedAsset $asset): ?string
    {
        if ($asset === null || ! class_exists(MimeTypes::class)) {
            return null;
        }

        $mime = MimeTypes::getDefault();
        return $mime->guessMimeType($asset->sourcePath);
    }
}
