<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use ArrayObject;
use function assert;
use function is_string;
use SpomkyLabs\PwaBundle\Dto\NoteTaking;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The configuration names the URL "note_taking_url", but the Manifest Incubations specification names the member
 * "new_note_url", and browsers ignore any other name. The serialized name of the DTO property is also the
 * configuration key it is denormalized from, so the member is renamed here, on output only.
 */
final class NoteTakingNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @return array{new_note_url?: string}|ArrayObject<string, never>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|ArrayObject
    {
        assert($data instanceof NoteTaking);
        if ($data->url === null) {
            return ($context[AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS] ?? false) === true ? new ArrayObject() : [];
        }

        $url = $this->normalizer->normalize($data->url, $format, $context);
        assert(is_string($url));

        return [
            'new_note_url' => $url,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof NoteTaking;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            NoteTaking::class => true,
        ];
    }
}
