<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\ProtocolHandler;
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
        if ($data->placeholder !== null) {
            $data->url->params = [
                ...$data->url->params,
                $data->placeholder => '%s',
            ];
        }

        $normalizedUrl = $this->normalizer->normalize($data->url, $format, $context);
        if ($data->placeholder !== null) {
            $encodedPlaceholder = urlencode($data->placeholder);

            // Construire le pattern de recherche
            $pattern = '/(?<=^|[&?])' . preg_quote($encodedPlaceholder, '/') . '=%25s(?=&|$)/';
            $replacement = "{$encodedPlaceholder}=%s";
            $normalizedUrl = preg_replace($pattern, $replacement, (string) $normalizedUrl);
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
