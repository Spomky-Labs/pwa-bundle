<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use function assert;
use SpomkyLabs\PwaBundle\Dto\Shortcut;
use SpomkyLabs\PwaBundle\Service\LocalizedMembersBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ShortcutNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const CTX_SKIP = 'shortcut_pre_normalization_skip';

    public function __construct(
        private readonly LocalizedMembersBuilder $localizedMembersBuilder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof Shortcut);
        $context[self::CTX_SKIP] = true;

        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizer->normalize($data, $format, $context);

        /** @var null|string $manifestDir */
        $manifestDir = $context[LocalizedMembersBuilder::MANIFEST_DIR_KEY] ?? null;

        return $this->localizedMembersBuilder->decorate($normalized, [
            'name' => $data->name,
            'short_name' => $data->shortName,
            'description' => $data->description,
        ], $manifestDir);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (! $data instanceof Shortcut) {
            return false;
        }

        return ($context[self::CTX_SKIP] ?? false) !== true;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Shortcut::class => false,
        ];
    }
}
