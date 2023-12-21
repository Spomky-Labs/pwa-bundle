<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use function count;
use function dirname;

#[AsCommand(name: 'pwa:sw', description: 'Initializes the Workbox-based Service Worker.',)]
class WorkboxInitCommand extends Command
{
    public function __construct(
        #[Autowire('%spomky_labs_pwa.dest%')]
        private readonly array               $dest,
        private readonly Filesystem $filesystem,
        private readonly FileLocator $fileLocator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Workbox Service Worker');

        if (! $this->filesystem->exists(dirname((string) $this->dest['serviceworker_filepath']))) {
            $this->filesystem->mkdir(dirname((string) $this->dest['serviceworker_filepath']));
        }

        $resourcePath = $this->fileLocator->locate('@SpomkyLabsPwaBundle/Resources/workbox.js', null, false);
        if (count($resourcePath) !== 1) {
            $io->error('Unable to find the Workbox resource.');
            return self::FAILURE;
        }
        $resourcePath = $resourcePath[0];
        $this->filesystem->copy($resourcePath, $this->dest['serviceworker_filepath']);

        $io->success('Workbox is ready to use!');

        return self::SUCCESS;
    }
}
