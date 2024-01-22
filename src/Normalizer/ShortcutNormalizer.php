<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Shortcut;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final class ShortcutNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @return array{description?: string, icons?: array, name: string, short_name?: string, url: string}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        assert($object instanceof Shortcut);

        $result = [
            'name' => $object->name,
            'short_name' => $object->shortName,
            'description' => $object->description,
            'url' => $this->normalizer->normalize($object->url, $format, $context),
            'icons' => $this->normalizer->normalize($object->icons, $format, $context),
        ];

        $cleanup = static fn (array $data): array => array_filter(
            $data,
            static fn ($value) => ($value !== null && $value !== [])
        );
        return $cleanup($result);
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Shortcut;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Shortcut::class => true,
        ];
    }
}
