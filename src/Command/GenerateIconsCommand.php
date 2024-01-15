<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use function count;

#[AsCommand(name: 'pwa:generate-icons', description: 'Generate icons for your PWA')]
final class GenerateIconsCommand extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ImageProcessor $imageProcessor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('source', InputArgument::REQUIRED, 'The source image');
        $this->addArgument('output', InputArgument::REQUIRED, 'The output directory');
        $this->addOption('format', null, InputOption::VALUE_OPTIONAL, 'The format of the icons');
        $this->addArgument(
            'sizes',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'The sizes of the icons',
            ['192', '512']
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Icons Generator');

        $source = $input->getArgument('source');
        $dest = $input->getArgument('output');
        $format = $input->getOption('format');
        $sizes = $input->getArgument('sizes');

        if (! $this->filesystem->exists($source)) {
            $io->info('The source image does not exist.');
            return self::FAILURE;
        }

        if (! $this->filesystem->exists($dest)) {
            $io->info('The output directory does not exist. It will be created.');
            $this->filesystem->mkdir($dest);
        }

        $mime = MimeTypes::getDefault();
        if ($format === null) {
            $mimeType = $mime->guessMimeType($source);
            $extensions = $mime->getExtensions($mimeType);
            if (count($extensions) === 0) {
                $io->error(sprintf('Unable to guess the extension for the mime type "%s".', $mimeType));
                return self::FAILURE;
            }
            $format = current($extensions);
        }

        foreach ($sizes as $size) {
            $io->info('Generating icon ' . $size . 'x' . $size . '...');
            $tmp = $this->imageProcessor->process(file_get_contents($source), (int) $size, (int) $size, $format);
            $filename = sprintf('%s/icon-%sx%s.%s', $dest, $size, $size, $format);
            $this->filesystem->dumpFile($filename, $tmp);
            $io->info('Icon ' . $size . 'x' . $size . ' generated.');
        }
        $io->info('Done.');

        return self::SUCCESS;
    }
}
