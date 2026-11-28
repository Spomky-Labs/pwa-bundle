<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use function array_filter;
use const ARRAY_FILTER_USE_BOTH;
use function array_key_exists;
use function assert;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Service\LocalizedMembersBuilder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\TranslatableNormalizer;

final class ManifestNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const CTX_SKIP = 'manifest_pre_normalization_skip';

    private const SPEC_DEFAULTS = [
        'prefer_related_applications' => false,
    ];

    public function __construct(
        private readonly LocalizedMembersBuilder $localizedMembersBuilder,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof Manifest);
        $context[self::CTX_SKIP] = true;
        $context[LocalizedMembersBuilder::MANIFEST_DIR_KEY] = $data->dir;
        $context[AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS] = true;

        /** @var array<string,mixed> $normalized */
        $normalized = $this->normalizer->normalize($data, $format, $context);
        $normalized = $this->localizedMembersBuilder->decorate($normalized, [
            'name' => $data->name,
            'short_name' => $data->shortName,
            'description' => $data->description,
        ], $data->dir);

        if (! array_key_exists('lang', $normalized) || $normalized['lang'] === null || $normalized['lang'] === '') {
            $normalized['lang'] = $context[TranslatableNormalizer::NORMALIZATION_LOCALE_KEY] ?? null;
        }

        return array_filter(
            $normalized,
            static fn (mixed $value, string $member): bool => $value !== null
                && $value !== []
                && (! array_key_exists($member, self::SPEC_DEFAULTS) || $value !== self::SPEC_DEFAULTS[$member]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (! $data instanceof Manifest) {
            return false;
        }

        if (($context[self::CTX_SKIP] ?? false) === true) {
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
