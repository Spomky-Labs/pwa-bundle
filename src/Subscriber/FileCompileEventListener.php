<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\FileCompilerInterface;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(PreAssetsCompileEvent::class)]
final class FileCompileEventListener implements CanLogInterface
{
    private LoggerInterface $logger;

    /**
     * @param iterable<FileCompilerInterface> $fileCompilers
     */
    public function __construct(
        #[AutowireIterator('spomky_labs_pwa.compiler')]
        private readonly iterable $fileCompilers,
        #[Autowire('@asset_mapper.local_public_assets_filesystem')]
        private readonly PublicAssetsFilesystemInterface $assetsFilesystem,
    ) {
        $this->logger = new NullLogger();
    }

    public function __invoke(PreAssetsCompileEvent $event): void
    {
        $this->logger->debug('Compiling files...');
        foreach ($this->fileCompilers as $fileCompiler) {
            $this->logger->debug('Compiling files with compiler.', [
                'compiler' => $fileCompiler,
            ]);
            foreach ($fileCompiler->getFiles() as $data) {
                $this->logger->debug('Compiling file.', [
                    'url' => $data->url,
                    'data' => $data,
                ]);
                $this->assetsFilesystem->write($data->url, $data->getData());
            }
        }
        $this->logger->debug('Files compiled.');
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
