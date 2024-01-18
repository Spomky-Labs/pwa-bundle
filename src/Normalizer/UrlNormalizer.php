<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;
use const FILTER_VALIDATE_URL;

final class UrlNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(
        private readonly RouterInterface $router,
        #[Autowire('%spomky_labs_pwa.routes.reference_type%')]
        private readonly int $referenceType,
    ) {
    }

    public function normalize(mixed $object, string $format = null, array $context = []): ?string
    {
        assert($object instanceof Url);

        if (! str_starts_with($object->path, '/') && ! filter_var($object->path, FILTER_VALIDATE_URL)) {
            return $this->router->generate($object->path, $object->params, $this->referenceType);
        }

        return $object->path;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Url;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Url::class => true,
        ];
    }
}
