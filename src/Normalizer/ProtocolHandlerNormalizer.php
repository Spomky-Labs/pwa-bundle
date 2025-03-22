<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\ProtocolHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function assert;

final class ProtocolHandlerNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @return array{protocol: string, url: string}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof ProtocolHandler);
        $url = $data->url;
        $url->pathTypeReference = UrlGeneratorInterface::ABSOLUTE_URL;
        if ($data->placeholder !== null) {
            $url->params = [
                ...$url->params,
                $data->placeholder => '%s',
            ];
        }

        $normalizedUrl = $this->normalizer->normalize($url, $format, $context);
        if ($data->placeholder !== null) {
            $encodedPlaceholder = urlencode($data->placeholder);

            // Construire le pattern de recherche
            $pattern = '/(?<=^|[&?])' . preg_quote($encodedPlaceholder, '/') . '=%25s(?=&|$)/';
            $replacement = "{$encodedPlaceholder}=%s";
            $normalizedUrl = preg_replace($pattern, $replacement, $normalizedUrl);
        }

        return [
            'protocol' => $data->protocol,
            'url' => $normalizedUrl,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
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
