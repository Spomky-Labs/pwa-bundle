<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class FileCompiler implements CanLogInterface
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

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function compile(): void
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
}
