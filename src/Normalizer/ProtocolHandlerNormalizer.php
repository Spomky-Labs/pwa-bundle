<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\ProtocolHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;
use const FILTER_VALIDATE_URL;

final readonly class ProtocolHandlerNormalizer implements NormalizerInterface
{
    public function __construct(
        private RouterInterface $router,
        #[Autowire('%spomky_labs_pwa.routes.reference_type%')]
        private int $referenceType,
    ) {
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        assert($object instanceof ProtocolHandler);
        $url = $object->url;
        if (! str_starts_with($url, '/') && ! filter_var($url, FILTER_VALIDATE_URL)) {
            $url = $this->router->generate($url, $object->urlParameters, $this->referenceType);
        }

        return [
            'url' => $url,
            'protocol' => $object->protocol,
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof ProtocolHandler;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            ProtocolHandler::class => true,
        ];
    }
}
