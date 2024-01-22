<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final readonly class ServiceWorkerNormalizer implements NormalizerInterface
{
    public function __construct(
        private AssetMapperInterface $assetMapper
    ) {
    }

    /**
     * @return array{scope?: string, src: string, use_cache?: bool}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        assert($object instanceof ServiceWorker);
        $url = null;
        if (! str_starts_with($object->dest, '/')) {
            $url = $this->assetMapper->getAsset($object->dest)?->publicPath;
        }
        if ($url === null) {
            $url = $object->dest;
        }

        $result = [
            'src' => $url,
            'scope' => $object->scope,
            'use_cache' => $object->useCache,
        ];

        $cleanup = static fn (array $data): array => array_filter(
            $data,
            static fn ($value) => ($value !== null && $value !== [])
        );
        return $cleanup($result);
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof ServiceWorker;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            ServiceWorker::class => true,
        ];
    }
}
