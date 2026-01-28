<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use function assert;
use SpomkyLabs\PwaBundle\Dto\Icon;
use SpomkyLabs\PwaBundle\Service\IconResolver;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
        assert($data instanceof Icon);
        $icon = $this->iconResolver->getIcon($data);
        $imageType = $this->iconResolver->getType($data->type, $icon->url);

        $result = [
            'src' => $icon->url,
            'sizes' => $data->getSizeList(),
            'type' => $imageType,
            'purpose' => $data->purpose,
        ];

        /** @var array{src: string, sizes?: string, type?: string, purpose?: string} */
        $result = array_filter($result, static fn ($value) => $value !== null);

        return $result;
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
