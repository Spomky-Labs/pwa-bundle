<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use SpomkyLabs\PwaBundle\Service\FileCompilerInterface;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(PreAssetsCompileEvent::class)]
final readonly class FileCompileEventListener
{
    /**
     * @param iterable<FileCompilerInterface> $fileCompilers
     */
    public function __construct(
        #[TaggedIterator('spomky_labs_pwa.compiler')]
        private iterable $fileCompilers,
        #[Autowire('@asset_mapper.local_public_assets_filesystem')]
        private PublicAssetsFilesystemInterface $assetsFilesystem,
    ) {
    }

    public function __invoke(PreAssetsCompileEvent $event): void
    {
        foreach ($this->fileCompilers as $fileCompiler) {
            foreach ($fileCompiler->getFiles() as $data) {
                $this->assetsFilesystem->write($data->url, $data->getData());
            }
        }
    }
}
