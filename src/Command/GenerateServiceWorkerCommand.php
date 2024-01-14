<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use function count;

#[AsCommand(name: 'pwa:sw', description: 'Generate a basic Service Worker')]
final class GenerateServiceWorkerCommand extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly FileLocator $fileLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('output', InputArgument::REQUIRED, 'The output file');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the generation of the service worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Service Worker Generator');

        $dest = $input->getArgument('output');
        $force = $input->getOption('force');

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

        return self::SUCCESS;
    }
}
