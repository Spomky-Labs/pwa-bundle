<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function sprintf;

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

    public function compile(?SymfonyStyle $io = null, bool $contextDependentOnly = false): void
    {
        $this->logger->debug('Compiling files...');
        foreach ($this->fileCompilers as $fileCompiler) {
            $io?->section(sprintf('Compiling files with %s', $fileCompiler::class));
            $this->logger->debug('Compiling files with compiler.', [
                'compiler' => $fileCompiler,
            ]);
            ProgressBar::setFormatDefinition('custom', '[%bar%] %message%');
            $progressBar = $io?->createProgressBar();
            $progressBar?->setBarWidth(100);
            $progressBar?->setFormat('custom');
            $progressBar?->setMessage('Start');
            $progressBar?->start();
            foreach ($fileCompiler->getFiles() as $data) {
                if ($contextDependentOnly === true && $data->contextFree === true) {
                    continue;
                }
                $progressBar?->advance();
                $progressBar?->setMessage(sprintf('Compiling %s', $data->url));
                $this->logger->debug('Compiling file.', [
                    'url' => $data->url,
                    'data' => $data,
                ]);
                $this->assetsFilesystem->write($data->url, $data->getData());
            }
            $progressBar?->finish();
        }
        $this->logger->debug('Files compiled.');
    }
}
