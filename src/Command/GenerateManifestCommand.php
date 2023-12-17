<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use JsonException;
use RuntimeException;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Mime\MimeTypes;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsCommand(name: 'pwa:build', description: 'Generate the Progressive Web App Manifest',)]
final class GenerateManifestCommand extends Command
{
    private readonly MimeTypes $mime;

    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly null|ImageProcessor $imageProcessor,
        #[Autowire('%spomky_labs_pwa.config%')]
        private readonly array $config,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $rootDir,
    ) {
        $this->mime = MimeTypes::getDefault();
        $this->filesystem = new Filesystem();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('public_url', InputArgument::OPTIONAL, 'Public URL', '/pwa');
        $this->addArgument('public_folder', InputArgument::OPTIONAL, 'Public folder', $this->rootDir . '/public');
        $this->addArgument('asset_folder', InputArgument::OPTIONAL, 'Asset folder', '/assets');
        $this->addArgument('output', InputArgument::OPTIONAL, 'Output file', 'manifest.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Manifest Generator');
        $manifest = $this->config;
        $manifest = array_filter($manifest, static fn ($value) => ($value !== null && $value !== []));

        $publicUrl = $input->getArgument('public_url');
        $publicFolder = Path::canonicalize($input->getArgument('public_folder'));
        $assetFolder = '/' . trim((string) $input->getArgument('asset_folder'), '/');
        $outputFile = '/' . trim((string) $input->getArgument('output'), '/');

        $this->createDirectory($publicFolder);

        $manifest = $this->processIcons($io, $manifest, $publicUrl, $publicFolder, $assetFolder);
        if ($manifest === self::FAILURE) {
            return self::FAILURE;
        }
        $manifest = $this->processScreenshots($io, $manifest, $publicUrl, $publicFolder, $assetFolder);
        if ($manifest === self::FAILURE) {
            return self::FAILURE;
        }
        $manifest = $this->processShortcutIcons($io, $manifest, $publicUrl, $publicFolder, $assetFolder);
        if ($manifest === self::FAILURE) {
            return self::FAILURE;
        }

        try {
            file_put_contents(
                sprintf('%s%s', $publicFolder, $outputFile),
                json_encode(
                    $manifest,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                )
            );
        } catch (JsonException $exception) {
            echo 'An error occurred while creating your directory at ' . $exception->getPath();
        }

        return self::SUCCESS;
    }

    private function createDirectory(string $folderPath): void
    {
        if ($this->filesystem->exists($folderPath)) {
            $this->filesystem->remove($folderPath);
        }
        $this->filesystem->mkdir($folderPath);
    }

    /**
     * @param array<string|null> $components
     * @return array{src: string, type: string}
     */
    private function storeFile(
        string $data,
        string $publicUrl,
        string $publicFolder,
        string $assetFolder,
        string $type,
        array  $components
    ): array {
        $tempFilename = $this->filesystem->tempnam($publicFolder, $type . '-');
        $hash = mb_substr(hash('sha256', $data), 0, 8);
        file_put_contents($tempFilename, $data);
        $mime = $this->mime->guessMimeType($tempFilename);
        $extension = $this->mime->getExtensions($mime);

        if (empty($extension)) {
            throw new RuntimeException(sprintf('Unable to guess the extension for the mime type "%s"', $mime));
        }

        $components[] = $hash;
        $filename = sprintf('%s/%s.%s', $assetFolder, implode('-', $components), $extension[0]);
        $localFilename = sprintf('%s%s', $publicFolder, $filename);

        file_put_contents($localFilename, $data);
        $this->filesystem->remove($tempFilename);

        return [
            'src' => sprintf('%s%s', $publicUrl, $filename),
            'type' => $mime,
        ];
    }

    /**
     * @return array{src: string, type: string, sizes: string, form_factor: ?string}
     */
    private function storeScreenshot(
        string  $data,
        string  $publicUrl,
        string  $publicFolder,
        string  $assetFolder,
        ?string $format,
        ?string $formFactor
    ): array {
        if ($format !== null) {
            $data = $this->imageProcessor->process($data, null, null, $format);
        }

        ['width' => $width, 'height' => $height] = $this->imageProcessor->getSizes($data);
        $size = sprintf('%sx%s', $width, $height);
        $formFactor ??= $width > $height ? 'wide' : 'narrow';

        $fileData = $this->storeFile(
            $data,
            $publicUrl,
            $publicFolder,
            $assetFolder,
            'screenshot',
            ['screenshot', $formFactor, $size]
        );

        return $fileData + [
            'sizes' => $size,
            'form_factor' => $formFactor,
        ];
    }

    /**
     * @return array{src: string, sizes: string, type: string, purpose: ?string}
     */
    private function storeShortcutIcon(
        string  $data,
        string  $publicUrl,
        string  $publicFolder,
        string  $assetFolder,
        string  $sizes,
        ?string $purpose
    ): array {
        $fileData = $this->storeFile(
            $data,
            $publicUrl,
            $publicFolder,
            $assetFolder,
            'shortcut-icon',
            ['shortcut-icon', $purpose]
        );

        return ($purpose !== null)
            ? $fileData + [
                'sizes' => $sizes,
                'purpose' => $purpose,
            ]
            : $fileData + [
                'sizes' => $sizes,
            ];
    }

    /**
     * @return array{src: string, sizes: string, type: string, purpose: ?string}
     */
    private function storeIcon(
        string  $data,
        string  $publicUrl,
        string  $publicFolder,
        string  $assetFolder,
        string  $sizes,
        ?string $purpose
    ): array {
        $fileData = $this->storeFile($data, $publicUrl, $publicFolder, $assetFolder, 'icon', ['icon', $purpose]);

        return ($purpose !== null)
            ? $fileData + [
                'sizes' => $sizes,
                'purpose' => $purpose,
            ]
            : $fileData + [
                'sizes' => $sizes,
            ];
    }

    private function processIcons(
        SymfonyStyle $io,
        array $manifest,
        mixed $publicUrl,
        string $publicFolder,
        string $assetFolder
    ): array|int {
        if ($this->config['icons'] === []) {
            return $manifest;
        }

        try {
            $this->filesystem->mkdir(sprintf('%s%s', $publicFolder, $assetFolder));
        } catch (IOExceptionInterface $exception) {
            echo 'An error occurred while creating your directory at ' . $exception->getPath();
        }
        $manifest['icons'] = [];
        $io->info('Processing icons');
        if ($this->imageProcessor === null) {
            $io->error('Image processor not found');
            return self::FAILURE;
        }
        foreach ($this->config['icons'] as $icon) {
            $minSize = min($icon['sizes']);
            $maxSize = max($icon['sizes']);
            if ($minSize === 0 && $maxSize !== 0) {
                $io->error('The icon size 0 ("any") must not be mixed with other sizes');
                return self::FAILURE;
            }
            $data = file_get_contents($icon['src']);
            if ($data === false) {
                $io->error(sprintf('Unable to read the icon "%s"', $icon['src']));
                return self::FAILURE;
            }
            if ($maxSize !== 0) {
                $data = $this->imageProcessor->process($data, $maxSize, $maxSize, $icon['format'] ?? null);
            }
            $sizes = $maxSize === 0 ? 'any' : implode(
                ' ',
                array_map(static fn (int $size): string => $size . 'x' . $size, $icon['sizes'])
            );
            $iconManifest = $this->storeIcon(
                $data,
                $publicUrl,
                $publicFolder,
                $assetFolder,
                $sizes,
                $icon['purpose'] ?? null
            );
            $manifest['icons'][] = $iconManifest;
        }

        return $manifest;
    }

    private function processScreenshots(
        SymfonyStyle $io,
        array $manifest,
        mixed $publicUrl,
        string $publicFolder,
        string $assetFolder
    ): array|int {
        if ($this->config['screenshots'] === []) {
            return $manifest;
        }
        try {
            $this->filesystem->mkdir(sprintf('%s%s', $publicFolder, $assetFolder));
        } catch (IOExceptionInterface $exception) {
            echo 'An error occurred while creating your directory at ' . $exception->getPath();
        }
        $manifest['screenshots'] = [];
        $io->info('Processing screenshots');
        if ($this->imageProcessor === null) {
            $io->error('Image processor not found');
            return self::FAILURE;
        }
        foreach ($this->config['screenshots'] as $screenshot) {
            $data = file_get_contents($screenshot['src']);
            if ($data === false) {
                $io->error(sprintf('Unable to read the screenshot "%s"', $screenshot['src']));
                return self::FAILURE;
            }
            $screenshotManifest = $this->storeScreenshot(
                $data,
                $publicUrl,
                $publicFolder,
                $assetFolder,
                $screenshot['format'] ?? null,
                $screenshot['form_factor'] ?? null
            );
            if (isset($screenshot['label'])) {
                $screenshotManifest['label'] = $screenshot['label'];
            }
            if (isset($screenshot['platform'])) {
                $screenshotManifest['platform'] = $screenshot['platform'];
            }
            $manifest['screenshots'][] = $screenshotManifest;
        }

        return $manifest;
    }

    private function processShortcutIcons(
        SymfonyStyle $io,
        array|int $manifest,
        mixed $publicUrl,
        string $publicFolder,
        string $assetFolder
    ): array|int {
        if ($this->config['shortcuts'] === []) {
            return $manifest;
        }
        try {
            $this->filesystem->mkdir(sprintf('%s%s', $publicFolder, $assetFolder));
        } catch (IOExceptionInterface $exception) {
            echo 'An error occurred while creating your directory at ' . $exception->getPath();
        }
        $manifest['shortcuts'] = [];
        $io->info('Processing schortcuts');
        foreach ($this->config['shortcuts'] as $shortcutConfig) {
            $shortcut = $shortcutConfig;
            if (isset($shortcut['icons'])) {
                unset($shortcut['icons']);
            }
            if (isset($shortcutConfig['icons'])) {
                if (! $this->checkImageProcessor($io)) {
                    return self::FAILURE;
                }
                foreach ($shortcutConfig['icons'] as $icon) {
                    $minSize = min($icon['sizes']);
                    $maxSize = max($icon['sizes']);
                    if ($minSize === 0 && $maxSize !== 0) {
                        $io->error('The icon size 0 ("any") must not be mixed with other sizes');
                        return self::FAILURE;
                    }

                    $data = file_get_contents($icon['src']);
                    if ($data === false) {
                        $io->error(sprintf('Unable to read the screenshot "%s"', $icon['src']));
                        return self::FAILURE;
                    }
                    if ($maxSize !== 0) {
                        $data = $this->imageProcessor->process($data, $maxSize, $maxSize, $icon['format'] ?? null);
                    }
                    $sizes = $maxSize === 0 ? 'any' : implode(
                        ' ',
                        array_map(static fn (int $size): string => $size . 'x' . $size, $icon['sizes'])
                    );

                    $iconManifest = $this->storeShortcutIcon(
                        $data,
                        $publicUrl,
                        $publicFolder,
                        $assetFolder,
                        $sizes,
                        $icon['purpose'] ?? null
                    );
                    $shortcut['icons'][] = $iconManifest;
                }
            }
            $manifest['shortcuts'][] = $shortcut;
        }
        $manifest['shortcuts'] = array_values($manifest['shortcuts']);

        return $manifest;
    }

    private function checkImageProcessor(SymfonyStyle $io): bool
    {
        if ($this->imageProcessor === null) {
            $io->error('Image processor not found');
            return false;
        }

        return true;
    }
}
