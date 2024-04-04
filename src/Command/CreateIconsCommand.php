<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
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
use Symfony\Component\Yaml\Yaml;
use function count;
use function is_string;

#[AsCommand(name: 'pwa:create:icons', description: 'Generate icons for your PWA')]
final class CreateIconsCommand extends Command
{
    public function __construct(
        private readonly AssetMapperInterface $assetMapper,
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly null|ImageProcessorInterface $imageProcessor,
    ) {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->imageProcessor !== null;
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
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_OPTIONAL,
            'The output directory',
            sprintf('%s/assets/icons/', $this->projectDir)
        );
        $this->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'The output directory', 'icon');
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
        if ($this->imageProcessor === null) {
            $io->error('The image processor is not enabled.');
            return self::FAILURE;
        }

        $source = $input->getArgument('source');
        $dest = rtrim((string) $input->getOption('output'), '/');
        $filename = $input->getOption('filename');
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
            $mimeType = $mime->guessMimeType($sourcePath);
            $extensions = $mime->getExtensions($mimeType);
            if (count($extensions) === 0) {
                $io->error(sprintf('Unable to guess the extension for the mime type "%s".', $mimeType));
                return self::FAILURE;
            }
            $format = current($extensions);
        }

        $generatedIcons = [];
        foreach ($sizes as $size) {
            $size = (int) $size;
            $outputSize = $size === 0 ? 'any' : sprintf('%sx%s', $size, $size);
            $io->info(sprintf('Processing icon %s', $outputSize));
            $tmp = $this->imageProcessor->process(
                file_get_contents($sourcePath),
                $size === 0 ? null : $size,
                $size === 0 ? null : $size,
                $format
            );
            $filePath = sprintf('%s/%s-%s.%s', $dest, $filename, $outputSize, $format);
            $this->filesystem->dumpFile($filePath, $tmp);
            $asset = $this->assetMapper->getAssetFromSourcePath($filePath);
            $config = [
                'src' => $asset === null ? $filePath : $asset->logicalPath,
                'sizes' => [$size],
            ];
            $destMimeType = $mime->guessMimeType($filePath);
            if ($destMimeType !== null) {
                $config['type'] = $destMimeType;
            }

            $generatedIcons[] = $config;
        }

        $io->success('Icons have been generated. You can now use them in your application configuration file.');
        $io->writeln(
            'Here is an example of a configuration file. Please indent it properly before using it in your application configuration file.'
        );
        $io->writeln('It can be used in the manifest, shortcuts or widgets sections.');
        $io->writeln('');
        $io->writeln(Yaml::dump([
            'yaml' => [
                'icons' => $generatedIcons,
            ],
        ], 10, 2));

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
