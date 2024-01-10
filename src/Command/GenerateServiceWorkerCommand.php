<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use function count;
use function dirname;

#[AsCommand(name: 'pwa:sw', description: 'Generate a basic Service Worker')]
final class GenerateServiceWorkerCommand extends Command
{
    public function __construct(
        #[Autowire('%spomky_labs_pwa.config%')]
        private readonly array $config,
        private readonly Filesystem $filesystem,
        private readonly FileLocator $fileLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the generation of the service worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Service Worker Generator');

        if (! isset($this->config['serviceworker'])) {
            $io->info('Service worker section is missing.');
            return self::SUCCESS;
        }

        $force = $input->getOption('force');

        $dest = $this->config['serviceworker']['filepath'];
        $scope = $this->config['serviceworker']['scope'];
        $src = $this->config['serviceworker']['src'];

        if (! $this->filesystem->exists(dirname((string) $dest))) {
            $this->filesystem->mkdir(dirname((string) $dest));
        }
        if ($this->filesystem->exists($dest) && ! $force) {
            $io->info('Service worker already exists. Skipping.');
            return self::SUCCESS;
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

        return self::SUCCESS;
    }
}
