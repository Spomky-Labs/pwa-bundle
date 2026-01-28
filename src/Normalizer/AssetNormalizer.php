<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use function assert;
use function is_string;
use SpomkyLabs\PwaBundle\Dto\Asset;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class AssetNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private AssetMapperInterface $assetMapper,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        assert($data instanceof Asset);
        $url = null;
        if (! str_starts_with($data->src, '/')) {
            $asset = $this->assetMapper->getAsset($data->src);
            $url = $asset?->publicPath;
        }
        if ($url === null) {
            $url = $data->src;
        }

        return $url;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        assert(is_string($data));

        return Asset::create($data);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Asset;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === Asset::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Asset::class => true,
        ];
    }
}
