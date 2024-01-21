<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use function count;
use function is_string;

#[AsCommand(name: 'pwa:create:icons', description: 'Generate icons for your PWA')]
final class CreateIconsCommand extends Command
{
    public function __construct(
        private readonly AssetMapperInterface $assetMapper,
        private readonly Filesystem $filesystem,
        private readonly ImageProcessor $imageProcessor,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'The source image or an asset logical path',
            null,
            ['images/logo.svg', '/home/foo/projetA/favicon.png']
        );
        $this->addArgument(
            'output',
            InputArgument::OPTIONAL,
            'The output directory',
            sprintf('%s/assets/icons/', $this->projectDir)
        );
        $this->addArgument('filename', InputArgument::OPTIONAL, 'The output directory', 'icon');
        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_OPTIONAL,
            'The format of the icons',
            null,
            ['png', 'jpg', 'webp']
        );
        $this->addArgument(
            'sizes',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'The sizes of the icons',
            ['16', '32', '48', '96', '144', '180', '256', '512', '1024']
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Icons Generator');

        $source = $input->getArgument('source');
        $dest = $input->getArgument('output');
        $filename = $input->getArgument('filename');
        $format = $input->getOption('format');
        $sizes = $input->getArgument('sizes');

        $sourcePath = $this->getSourcePath($source);
        if (! is_string($sourcePath)) {
            $io->info('The source image does not exist.');
            return self::FAILURE;
        }

        if (! $this->filesystem->exists($dest)) {
            $io->info('The output directory does not exist. It will be created.');
            $this->filesystem->mkdir($dest);
        }

        $mime = MimeTypes::getDefault();
        if ($format === null) {
            dump($sourcePath);
            $mimeType = $mime->guessMimeType($sourcePath);
            $extensions = $mime->getExtensions($mimeType);
            if (count($extensions) === 0) {
                $io->error(sprintf('Unable to guess the extension for the mime type "%s".', $mimeType));
                return self::FAILURE;
            }
            $format = current($extensions);
        }

        foreach ($sizes as $size) {
            $io->info('Generating icon ' . $size . 'x' . $size . '...');
            $tmp = $this->imageProcessor->process(file_get_contents($sourcePath), (int) $size, (int) $size, $format);
            $this->filesystem->dumpFile(sprintf('%s/%s-%sx%s.%s', $dest, $filename, $size, $size, $format), $tmp);
            $io->info('Icon ' . $size . 'x' . $size . ' generated.');
        }
        $io->info('Done.');

        return self::SUCCESS;
    }

    private function getSourcePath(string $source): int|string
    {
        $asset = $this->assetMapper->getAsset($source);
        if ($asset !== null) {
            return $asset->sourcePath;
        }
        if (! $this->filesystem->exists($source)) {
            return self::FAILURE;
        }
        return $source;
    }
}
