<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use function count;

/**
 * @deprecated This command will be removed in the next major version. Create an empty file in assets/sw.js instead.

 */
#[AsCommand(name: 'pwa:create:sw', description: 'Generate a basic Service Worker')]
final class CreateServiceWorkerCommand extends Command
{
    public function __construct(
        private readonly AssetMapperInterface $assetMapper,
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'output',
            InputArgument::OPTIONAL,
            'The output file',
            sprintf('%s/assets/sw.js', $this->projectDir)
        );
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the generation of the service worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Service Worker Generator');

        $dest = $input->getArgument('output');
        $force = (bool) $input->getOption('force');

        if ($this->filesystem->exists($dest) && ! $force) {
            $io->info('Service worker already exists. Skipping.');
            return self::SUCCESS;
        }

        $fileLocator = new FileLocator(__DIR__ . '/../Resources');
        $resourcePath = $fileLocator->locate('sw-skeleton.js', null, false);
        if (count($resourcePath) !== 1) {
            $io->error('Unable to find the Workbox resource.');
            return Command::FAILURE;
        }
        $resourcePath = $resourcePath[0];
        $this->filesystem->copy($resourcePath, $dest);
        $asset = $this->assetMapper->getAssetFromSourcePath($dest);

        $config = [
            'src' => $asset === null ? $dest : $asset->logicalPath,
        ];

        $io->info('Service worker generated. You can now use it in your application configuration file.');
        $io->writeln(Yaml::dump([
            'serviceworker' => $config,
        ], 10, 2));

        return self::SUCCESS;
    }
}
