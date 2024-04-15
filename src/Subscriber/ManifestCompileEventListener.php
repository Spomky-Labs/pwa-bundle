<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsEventListener(PreAssetsCompileEvent::class)]
final readonly class ManifestCompileEventListener
{
    private string $manifestPublicUrl;

    private array $jsonOptions;

    public function __construct(
        private SerializerInterface $serializer,
        private Manifest $manifest,
        #[Autowire('%spomky_labs_pwa.manifest.enabled%')]
        private bool $manifestEnabled,
        #[Autowire('%spomky_labs_pwa.manifest.public_url%')]
        string $manifestPublicUrl,
        #[Autowire('@asset_mapper.local_public_assets_filesystem')]
        private PublicAssetsFilesystemInterface $assetsFilesystem,
        #[Autowire('%kernel.debug%')]
        bool $debug,
    ) {
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
        $options = [
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['useCredentials'],
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ];
        if ($debug === true) {
            $options[JsonEncode::OPTIONS] |= JSON_PRETTY_PRINT;
        }
        $this->jsonOptions = $options;
    }

    public function __invoke(PreAssetsCompileEvent $event): void
    {
        if (! $this->manifestEnabled) {
            return;
        }
        $data = $this->serializer->serialize($this->manifest, 'json', $this->jsonOptions);
        $this->assetsFilesystem->write($this->manifestPublicUrl, $data);
    }
}
