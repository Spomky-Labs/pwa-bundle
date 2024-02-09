<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\Dto\Icon;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Yaml\Yaml;
use function count;
use function is_array;

#[AsCommand(name: 'pwa:create:icons-from-config', description: 'Generate icons for your PWA from configuration')]
final class CreateIconsFromConfigurationCommand extends Command
{
    private readonly MimeTypes $mime;

    public function __construct(
        private readonly Manifest $manifest,
        private readonly Filesystem $filesystem,
        private readonly null|ImageProcessor $imageProcessor,
        private readonly AssetMapperInterface $assetMapper,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
        $this->mime = MimeTypes::getDefault();
    }

    public function isEnabled(): bool
    {
        return $this->imageProcessor !== null;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('output', null, 'Output directory', $this->projectDir . '/assets/icons');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Icons Generator');
        if ($this->imageProcessor === null) {
            $io->error('The image processor is not enabled.');
            return self::FAILURE;
        }
        $configuration = $this->manifest;

        $outputDirectory = $input->getArgument('output');
        $applicationIcons = [];
        foreach ($this->manifest->icons as $icon) {
            $applicationIcons = $this->generateIcons($icon, $io, $applicationIcons, $outputDirectory);
        }
        $configuration->icons = $applicationIcons;
        foreach ($this->manifest->shortcuts as $key => $shortcut) {
            $shortcutIcons = [];
            foreach ($shortcut->icons as $icon) {
                $shortcutIcons = $this->generateIcons($icon, $io, $shortcutIcons, $outputDirectory);
            }
            $this->manifest->shortcuts[$key]->icons = $shortcutIcons;
        }
        foreach ($this->manifest->widgets as $key => $widget) {
            $widgetIcons = [];
            foreach ($widget->icons as $icon) {
                $widgetIcons = $this->generateIcons($icon, $io, $widgetIcons, $outputDirectory);
            }
            $this->manifest->widgets[$key]->icons = $widgetIcons;
        }

        $io->success('Icons have been generated. You can now use them in your application configuration file.');
        $io->writeln(Yaml::dump([
            'pwa' => $configuration,
        ], 10, 2));

        return self::SUCCESS;
    }

    private function generateIcons(Icon $icon, SymfonyStyle $io, array $generatedIcons, string $outputDirectory): array
    {
        $sourcePath = null;
        if (! str_starts_with($icon->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($icon->src->src);
            $sourcePath = $asset->sourcePath;
        }
        if ($sourcePath === null) {
            $sourcePath = $icon->src->src;
        }
        $content = file_get_contents($sourcePath);
        if ($content === false) {
            $io->info(sprintf('Unable to read the file "%s". Skipping', $icon->src->src));
            return [];
        }

        $pathinfo = pathinfo((string) $sourcePath);
        $filenameRoot = $outputDirectory . '/' . $pathinfo['filename'];

        foreach ($icon->sizeList as $size) {
            if ($size !== 0) {
                $newIcon = $this->imageProcessor->process($content, $size, $size, $icon->format ?? null);
            } else {
                $newIcon = $content;
            }
            $tmpFilename = $this->filesystem->tempnam('', 'pwa');
            $this->filesystem->dumpFile($tmpFilename, $newIcon);
            $result = $this->getFormat($tmpFilename);
            if ($result === null) {
                $io->info(sprintf('Unable to guess the format for the file "%s". Skipping', $icon->src->src));
                continue;
            }
            ['extension' => $extension, 'format' => $format] = $result;
            $filename = sprintf('%s-%sx%s.%s', $filenameRoot, $size, $size, $extension);
            if (! $this->filesystem->exists($filename)) {
                $this->filesystem->copy($tmpFilename, $filename);
            }
            $this->filesystem->remove($tmpFilename);
            $asset = $this->assetMapper->getAssetFromSourcePath($filename);

            $iconStatement = [
                'src' => $asset === null ? $filename : $asset->logicalPath,
                'sizes' => $size,
                'purpose' => $icon->purpose ?? null,
            ];
            $iconStatement = array_filter($iconStatement, fn (mixed $value) => $value !== null);
            $generatedIcons[] = $iconStatement;
        }
        return $generatedIcons;
    }

    /**
     * @return array{extension: string, format: string}|null
     */
    private function getFormat(string $sourcePath): ?array
    {
        $mimeType = $this->mime->guessMimeType($sourcePath);
        if ($mimeType === null) {
            return null;
        }
        $extensions = $this->mime->getExtensions($mimeType);
        if (count($extensions) === 0) {
            return null;
        }

        return [
            'extension' => current($extensions),
            'format' => $mimeType,
        ];
    }

    /**
     * @param array<int|string, mixed> $data
     * @return array<int|string, mixed>
     */
    private static function cleanup(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::cleanup($value);
            }
        }
        return array_filter($data, fn (mixed $value) => ($value !== null && $value !== [] && $value !== ''));
    }
}
