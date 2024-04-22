<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use Psr\EventDispatcher\EventDispatcherInterface;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Event\NullEventDispatcher;
use SpomkyLabs\PwaBundle\Event\PostManifestCompileEvent;
use SpomkyLabs\PwaBundle\Event\PreManifestCompileEvent;
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
    private EventDispatcherInterface $dispatcher;

    private string $manifestPublicUrl;

    private array $jsonOptions;

    public function __construct(
        private SerializerInterface $serializer,
        private Manifest $manifest,
        #[Autowire('%spomky_labs_pwa.manifest.public_url%')]
        string $manifestPublicUrl,
        #[Autowire('@asset_mapper.local_public_assets_filesystem')]
        private PublicAssetsFilesystemInterface $assetsFilesystem,
        #[Autowire('%kernel.debug%')]
        bool $debug,
        null|EventDispatcherInterface $dispatcher = null,
    ) {
        $this->dispatcher = $dispatcher ?? new NullEventDispatcher();
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
        if (! $this->manifest->enabled) {
            return;
        }
        $manifest = clone $this->manifest;
        $this->dispatcher->dispatch(new PreManifestCompileEvent($manifest));
        $data = $this->serializer->serialize($manifest, 'json', $this->jsonOptions);
        $postEvent = new PostManifestCompileEvent($manifest, $data);
        $this->dispatcher->dispatch($postEvent);
        $this->assetsFilesystem->write($this->manifestPublicUrl, $postEvent->data);
    }
}
