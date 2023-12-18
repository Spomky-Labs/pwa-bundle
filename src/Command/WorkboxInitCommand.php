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
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\Config\FileLocator;
use function count;

#[AsCommand(name: 'pwa:sw', description: 'Initializes the Workbox-based Service Worker.',)]
class WorkboxInitCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $rootDir,
        private readonly Filesystem $filesystem,
        private readonly FileLocator $fileLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'public_folder',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Public folder',
                $this->rootDir . '/public'
            )
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file', 'sw.js')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Workbox Service Worker');

        $publicFolder = Path::canonicalize($input->getOption('public_folder'));
        $outputFile = '/' . trim((string) $input->getOption('output'), '/');

        if (! $this->filesystem->exists($publicFolder)) {
            $this->filesystem->mkdir($publicFolder);
        }

        $resourcePath = $this->fileLocator->locate('@SpomkyLabsPwaBundle/Resources/workbox.js', null, false);
        if (count($resourcePath) !== 1) {
            $io->error('Unable to find the Workbox resource.');
            return self::FAILURE;
        }
        $resourcePath = $resourcePath[0];
        $this->filesystem->copy($resourcePath, $publicFolder . $outputFile);

        $io->success('Workbox is ready to use!');

        return self::SUCCESS;
    }
}
