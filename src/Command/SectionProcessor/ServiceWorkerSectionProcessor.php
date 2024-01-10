<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use function count;
use function dirname;

final readonly class ServiceWorkerSectionProcessor implements SectionProcessor
{
    public function __construct(
        private Filesystem $filesystem,
        private FileLocator $fileLocator,
    ) {
    }

    public function process(SymfonyStyle $io, array $config, array $manifest): array|int
    {
        if (! isset($manifest['serviceworker'])) {
            $io->error('Service worker generation is disabled. Skipping.');
            return $manifest;
        }
        $generate = $manifest['serviceworker']['generate'];
        unset($manifest['serviceworker']['generate']);

        if ($generate !== true) {
            $io->info('Service worker generation is disabled. Skipping.');
            return $manifest;
        }

        $dest = $manifest['serviceworker']['filepath'];
        $scope = $manifest['serviceworker']['scope'];
        $src = $manifest['serviceworker']['src'];
        unset($manifest['serviceworker']['filepath']);

        if (! $this->filesystem->exists(dirname((string) $dest))) {
            $this->filesystem->mkdir(dirname((string) $dest));
        }
        if ($this->filesystem->exists($dest)) {
            $io->info('Service worker already exists. Skipping.');
            return $manifest;
        }

        $resourcePath = $this->fileLocator->locate('@SpomkyLabsPwaBundle/Resources/workbox.js', null, false);
        if (count($resourcePath) !== 1) {
            $io->error('Unable to find the Workbox resource.');
            return Command::FAILURE;
        }
        $resourcePath = $resourcePath[0];
        $this->filesystem->copy($resourcePath, $dest);
        $io->info('Service worker generated.');
        $io->comment('You can now configure your web server to serve the service worker file.');
        $io->section('# assets/app.js (or any other entrypoint)');
        $jsTemplate = <<<JS
            if (navigator.serviceWorker) {
                window.addEventListener("load", () => {
                    navigator.serviceWorker.register("{$src}", {scope: '{$scope}'});
                })
            }
        JS;

        $io->writeln($jsTemplate);
        $io->section('# End of file');

        return $manifest;
    }
}
