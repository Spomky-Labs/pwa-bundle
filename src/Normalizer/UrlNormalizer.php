<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Normalizer;

use SpomkyLabs\PwaBundle\Dto\Url;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;
use function assert;
use const FILTER_VALIDATE_URL;

final class UrlNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly AssetMapperInterface $assetMapper,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        assert($data instanceof Url);

        // If the path is a valid URL, we return it directly
        if (str_starts_with($data->path, '/') && filter_var($data->path, FILTER_VALIDATE_URL) !== false) {
            return $data->path;
        }

        // If the path is an asset, we return the public path
        $asset = $this->assetMapper->getAsset($data->path);
        if ($asset !== null) {
            return $asset->publicPath;
        }

        // Otherwise, we try to generate the URL
        try {
            $params = [
                '_locale' => $context['translatable_normalization_locale'] ?? null,
                ...$data->params,
            ];
            return $this->router->generate($data->path, $params, $data->pathTypeReference);
        } catch (Throwable) {
            // If the URL cannot be generated, we return the path as is
            return $data->path;
        }
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
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
