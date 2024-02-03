<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use SpomkyLabs\PwaBundle\Service\ServiceWorkerCompiler;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(PreAssetsCompileEvent::class)]
final readonly class ServiceWorkerCompileEventListener
{
    private ?string $serviceWorkerPublicUrl;

    public function __construct(
        private ServiceWorkerCompiler $serviceWorkerBuilder,
        #[Autowire('%spomky_labs_pwa.sw.enabled%')]
        private bool $serviceWorkerEnabled,
        #[Autowire('%spomky_labs_pwa.sw.public_url%')]
        ?string $serviceWorkerPublicUrl,
        #[Autowire('@asset_mapper.local_public_assets_filesystem')]
        private PublicAssetsFilesystemInterface $assetsFilesystem,
    ) {
        $this->serviceWorkerPublicUrl = $serviceWorkerPublicUrl === null ? null : '/' . trim(
            $serviceWorkerPublicUrl,
            '/'
        );
    }

    public function __invoke(PreAssetsCompileEvent $event): void
    {
        if (! $this->serviceWorkerEnabled) {
            return;
        }
        $data = $this->serviceWorkerBuilder->compile();
        if ($data === null || $this->serviceWorkerPublicUrl === null) {
            return;
        }
        $this->assetsFilesystem->write($this->serviceWorkerPublicUrl, $data);
    }
}
