<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\TranslatableNormalizer;
use function array_key_exists;
use function assert;

final class ManifestNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const CTX_SKIP = 'manifest_pre_normalization_skip';

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof Manifest);
        $context[self::CTX_SKIP] = true;

        /** @var array<string,mixed> $normalized */
        $normalized = $this->normalizer->normalize($data, $format, $context);

        if (array_key_exists('lang', $normalized) && $normalized['lang'] !== null && $normalized['lang'] !== '') {
            return $normalized;
        }

        $normalized['lang'] = $context[TranslatableNormalizer::NORMALIZATION_LOCALE_KEY] ?? null;

        return $normalized;

    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (! $data instanceof Manifest) {
            return false;
        }

        if (! empty($context[self::CTX_SKIP])) {
            return false;
        }

        return true;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Manifest::class => false,
        ];
    }
}
