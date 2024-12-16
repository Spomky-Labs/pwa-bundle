<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final readonly class ServiceWorkerNormalizer implements NormalizerInterface
{
    /**
     * @return array{scope?: string, src: string, use_cache?: bool}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof ServiceWorker);

        $result = [
            'src' => '/' . trim($data->dest, '/'),
            'scope' => $data->scope,
            'use_cache' => $data->useCache,
        ];

        $cleanup = static fn (array $data): array => array_filter(
            $data,
            static fn ($value) => ($value !== null && $value !== [])
        );
        return $cleanup($result);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
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
