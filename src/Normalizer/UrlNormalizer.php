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

    public function normalize(mixed $object, string $format = null, array $context = []): string
    {
        assert($object instanceof Url);

        // If the path is a valid URL, we return it directly
        if (str_starts_with($object->path, '/') && filter_var($object->path, FILTER_VALIDATE_URL) !== false) {
            return $object->path;
        }

        // If the path is an asset, we return the public path
        $asset = $this->assetMapper->getAsset($object->path);
        if ($asset !== null) {
            return $asset->publicPath;
        }

        // Otherwise, we try to generate the URL
        try {
            return $this->router->generate($object->path, $object->params, $object->pathTypeReference);
        } catch (Throwable) {
            // If the URL cannot be generated, we return the path as is
            return $object->path;
        }
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
