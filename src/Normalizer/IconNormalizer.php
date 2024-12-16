<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Icon;
use SpomkyLabs\PwaBundle\Service\IconResolver;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final class IconNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(
        private readonly IconResolver $iconResolver,
    ) {
    }

    /**
     * @return array{src: string, sizes?: string, type?: string, purpose?: string}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($object instanceof Icon);
        $icon = $this->iconResolver->getIcon($object);
        $imageType = $this->iconResolver->getType($object->type, $icon->url);

        $result = [
            'src' => $icon->url,
            'sizes' => $object->getSizeList(),
            'type' => $imageType,
            'purpose' => $data->purpose,
        ];

        $cleanup = static fn (array $data): array => array_filter(
            $data,
            static fn ($value) => ($value !== null && $value !== [])
        );

        return $cleanup($result);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
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
}
