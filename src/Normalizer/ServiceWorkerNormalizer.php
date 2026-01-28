<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use function assert;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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

        /** @var array{scope?: string, src: string, use_cache?: bool} */
        $result = array_filter($result, static fn ($value) => $value !== null);
        return $result;
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
