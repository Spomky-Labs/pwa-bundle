<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsEventListener(PreAssetsCompileEvent::class)]
final readonly class AssetsCompileEventListener
{
    private string $manifestPublicUrl;

    public function __construct(
        private SerializerInterface $serializer,
        private Manifest $manifest,
        #[Autowire('%spomky_labs_pwa.manifest_public_url%')]
        string $manifestPublicUrl,
        #[Autowire('@asset_mapper.local_public_assets_filesystem')]
        private PublicAssetsFilesystemInterface $assetsFilesystem,
    ) {
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
    }

    public function __invoke(PreAssetsCompileEvent $event): void
    {
        $data = $this->serializer->serialize($this->manifest, 'json', [
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);
        $this->assetsFilesystem->write($this->manifestPublicUrl, $data);
    }
}
