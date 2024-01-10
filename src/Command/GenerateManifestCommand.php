<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use Facebook\WebDriver\WebDriverDimension;
use JsonException;
use RuntimeException;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Panther\Client;
use Symfony\Component\Routing\RouterInterface;
use function count;
use function dirname;
use function is_array;
use function is_int;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsCommand(name: 'pwa:build', description: 'Generate the Progressive Web App Manifest',)]
final class GenerateManifestCommand extends Command
{
    private readonly MimeTypes $mime;

    private readonly null|Client $webClient;

    public function __construct(
        private readonly null|ImageProcessor $imageProcessor,
        #[Autowire('@pwa.web_client')]
        null|Client $webClient,
        #[Autowire('%spomky_labs_pwa.config%')]
        private readonly array $config,
        #[Autowire('%spomky_labs_pwa.dest%')]
        private readonly array $dest,
        private readonly Filesystem $filesystem,
        private readonly FileLocator $fileLocator,
        private readonly ?RouterInterface $router = null,
    ) {
        if ($webClient === null && class_exists(Client::class)) {
            $webClient = Client::createChromeClient();
        }
        $this->webClient = $webClient;
        $this->mime = MimeTypes::getDefault();
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Manifest Generator');
        $manifest = $this->config;
        $manifest = array_filter($manifest, static fn ($value) => ($value !== null && $value !== []));

        $manifest = $this->processIcons($io, $manifest);
        if (! is_array($manifest)) {
            return self::FAILURE;
        }
        $manifest = $this->processScreenshots($io, $manifest);
        if (! is_array($manifest)) {
            return self::FAILURE;
        }
        $manifest = $this->processShortcutIcons($io, $manifest);
        if (! is_array($manifest)) {
            return self::FAILURE;
        }
        $manifest = $this->processActions($io, $manifest);
        if (! is_array($manifest)) {
            return self::FAILURE;
        }
        $manifest = $this->processServiceWorker($io, $manifest);
        if (! is_array($manifest)) {
            return self::FAILURE;
        }

        try {
            $this->createDirectoryIfNotExists(dirname((string) $this->dest['manifest_filepath']));
            if (! $this->filesystem->exists($this->dest['manifest_filepath'])) {
                $this->filesystem->remove($this->dest['manifest_filepath']);
            }
            file_put_contents(
                (string) $this->dest['manifest_filepath'],
                json_encode(
                    $manifest,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                )
            );
        } catch (JsonException $exception) {
            $io->error(sprintf('Unable to generate the manifest file: %s', $exception->getMessage()));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string|null> $components
     * @return array{src: string, type: string}
     */
    private function storeFile(string $data, string $prefixUrl, string $storageFolder, array $components): array
    {
        $tempFilename = $this->filesystem->tempnam($storageFolder, 'pwa-');
        $hash = mb_substr(hash('sha256', $data), 0, 8);
        file_put_contents($tempFilename, $data);
        $mime = $this->mime->guessMimeType($tempFilename);
        $extension = $this->mime->getExtensions($mime);

        if (empty($extension)) {
            throw new RuntimeException(sprintf('Unable to guess the extension for the mime type "%s"', $mime));
        }

        $components[] = $hash;
        $filename = sprintf('%s.%s', implode('-', $components), $extension[0]);
        $localFilename = sprintf('%s/%s', rtrim($storageFolder, '/'), $filename);

        file_put_contents($localFilename, $data);
        $this->filesystem->remove($tempFilename);

        return [
            'src' => sprintf('%s/%s', $prefixUrl, $filename),
            'type' => $mime,
        ];
    }

    /**
     * @return array{src: string, type: string, sizes: string, form_factor: ?string}
     */
    private function storeScreenshot(string $data, ?string $format, ?string $formFactor): array
    {
        if ($format !== null) {
            $data = $this->imageProcessor->process($data, null, null, $format);
        }

        ['width' => $width, 'height' => $height] = $this->imageProcessor->getSizes($data);
        $size = sprintf('%sx%s', $width, $height);

        $fileData = $this->storeFile(
            $data,
            $this->dest['screenshot_prefix_url'],
            $this->dest['screenshot_folder'],
            ['screenshot', $formFactor, $size]
        );
        if ($formFactor !== null) {
            $fileData += [
                'form_factor' => $formFactor,
            ];
        }

        return $fileData + [
            'sizes' => $size,
        ];
    }

    private function handleSizeAndPurpose(?string $purpose, int $size, array $fileData): array
    {
        $sizes = $size === 0 ? 'any' : $size . 'x' . $size;
        $fileData += [
            'sizes' => $sizes,
        ];

        if ($purpose !== null) {
            $fileData += [
                'purpose' => $purpose,
            ];
        }

        return $fileData;
    }

    /**
     * @return array{src: string, sizes: string, type: string, purpose: ?string}
     */
    private function storeShortcutIcon(string $data, int $size, ?string $purpose): array
    {
        $fileData = $this->storeFile(
            $data,
            $this->dest['shortcut_icon_prefix_url'],
            $this->dest['shortcut_icon_folder'],
            ['shortcut-icon', $purpose, $size === 0 ? 'any' : $size . 'x' . $size]
        );

        return $this->handleSizeAndPurpose($purpose, $size, $fileData);
    }

    /**
     * @return array{src: string, sizes: string, type: string, purpose: ?string}
     */
    private function storeIcon(string $data, int $size, ?string $purpose): array
    {
        $fileData = $this->storeFile(
            $data,
            $this->dest['icon_prefix_url'],
            $this->dest['icon_folder'],
            ['icon', $purpose, $size === 0 ? 'any' : $size . 'x' . $size]
        );

        return $this->handleSizeAndPurpose($purpose, $size, $fileData);
    }

    private function processIcons(SymfonyStyle $io, array $manifest): array|int
    {
        if ($this->config['icons'] === []) {
            return $manifest;
        }
        if (! $this->createDirectoryIfNotExists($this->dest['icon_folder']) || ! $this->checkImageProcessor($io)) {
            return self::FAILURE;
        }
        $manifest['icons'] = [];
        foreach ($this->config['icons'] as $icon) {
            foreach ($icon['sizes'] as $size) {
                if (! is_int($size) || $size < 0) {
                    $io->error('The icon size must be a positive integer');
                    return self::FAILURE;
                }
                $data = $this->loadFileAndConvert($icon['src'], $size, $icon['format'] ?? null);
                if ($data === null) {
                    $io->error(sprintf('Unable to read the icon "%s"', $icon['src']));
                    return self::FAILURE;
                }

                $iconManifest = $this->storeIcon($data, $size, $icon['purpose'] ?? null);
                $manifest['icons'][] = $iconManifest;
            }
        }
        $io->info('Icons are built');

        return $manifest;
    }

    private function processScreenshots(SymfonyStyle $io, array $manifest): array|int
    {
        if ($this->config['screenshots'] === []) {
            return $manifest;
        }
        if (! $this->createDirectoryIfNotExists($this->dest['screenshot_folder']) || ! $this->checkImageProcessor(
            $io
        )) {
            return self::FAILURE;
        }
        $manifest['screenshots'] = [];
        $config = [];
        foreach ($this->config['screenshots'] as $screenshot) {
            if (isset($screenshot['src'])) {
                $src = $screenshot['src'];
                if (! $this->filesystem->exists($src)) {
                    continue;
                }
                foreach ($this->findImages($src) as $image) {
                    $data = $screenshot;
                    $data['src'] = $image;
                    $config[] = $data;
                }
            }
            if (isset($screenshot['path'])) {
                $path = $screenshot['path'];
                $height = $screenshot['height'];
                $width = $screenshot['width'];
                unset($screenshot['path'], $screenshot['height'], $screenshot['width']);

                $client = clone $this->webClient;
                $client->request('GET', $path);
                $tmpName = $this->filesystem->tempnam('', 'pwa-');
                $client->manage()
                    ->window()
                    ->setSize(new WebDriverDimension($width, $height));
                $client->manage()
                    ->window()
                    ->fullscreen();
                $client->takeScreenshot($tmpName);
                $data = $screenshot;
                $data['src'] = $tmpName;
                $data['delete'] = true;
                $config[] = $data;
            }
        }

        foreach ($config as $screenshot) {
            $data = $this->loadFileAndConvert($screenshot['src'], null, $screenshot['format'] ?? null);
            if ($data === null) {
                $io->error(sprintf('Unable to read the icon "%s"', $screenshot['src']));
                return self::FAILURE;
            }
            $delete = $screenshot['delete'] ?? false;
            unset($screenshot['delete']);
            $screenshotManifest = $this->storeScreenshot(
                $data,
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
            if ($delete) {
                $this->filesystem->remove($screenshot['src']);
            }
        }

        return $manifest;
    }

    private function processShortcutIcons(SymfonyStyle $io, array|int $manifest): array|int
    {
        if ($this->config['shortcuts'] === []) {
            return $manifest;
        }
        if (! $this->createDirectoryIfNotExists($this->dest['shortcut_icon_folder']) || ! $this->checkImageProcessor(
            $io
        )) {
            return self::FAILURE;
        }
        $manifest['shortcuts'] = [];
        foreach ($this->config['shortcuts'] as $shortcutConfig) {
            $shortcut = $shortcutConfig;
            if (isset($shortcut['icons'])) {
                unset($shortcut['icons']);
            }
            if (! str_starts_with((string) $shortcut['url'], '/')) {
                if ($this->router === null) {
                    $io->error('The router is not available');
                    return self::FAILURE;
                }
                $shortcut['url'] = $this->router->generate($shortcut['url'], [], RouterInterface::RELATIVE_PATH);
            }

            if (isset($shortcutConfig['icons'])) {
                if (! $this->checkImageProcessor($io)) {
                    return self::FAILURE;
                }
                foreach ($shortcutConfig['icons'] as $icon) {
                    foreach ($icon['sizes'] as $size) {
                        if (! is_int($size) || $size < 0) {
                            $io->error('The icon size must be a positive integer');
                            return self::FAILURE;
                        }

                        $data = $this->loadFileAndConvert($icon['src'], $size, $icon['format'] ?? null);
                        if ($data === null) {
                            $io->error(sprintf('Unable to read the icon "%s"', $icon['src']));
                            return self::FAILURE;
                        }

                        $iconManifest = $this->storeShortcutIcon($data, $size, $icon['purpose'] ?? null);
                        $shortcut['icons'][] = $iconManifest;
                    }
                }
            }
            $manifest['shortcuts'][] = $shortcut;
        }
        $manifest['shortcuts'] = array_values($manifest['shortcuts']);

        return $manifest;
    }

    private function loadFileAndConvert(string $src, ?int $size, ?string $format): ?string
    {
        $data = file_get_contents($src);
        if ($data === false) {
            return null;
        }
        if ($size !== 0 && $size !== null) {
            $data = $this->imageProcessor->process($data, $size, $size, $format);
        }

        return $data;
    }

    private function checkImageProcessor(SymfonyStyle $io): bool
    {
        if ($this->imageProcessor === null) {
            $io->error('Image processor not found');
            return false;
        }

        return true;
    }

    private function createDirectoryIfNotExists(string $folder): bool
    {
        try {
            if (! $this->filesystem->exists($folder)) {
                $this->filesystem->mkdir($folder);
            }
        } catch (IOExceptionInterface) {
            return false;
        }

        return true;
    }

    private function processActions(SymfonyStyle $io, array $manifest): array|int
    {
        if ($this->config['file_handlers'] === []) {
            return $manifest;
        }
        foreach ($manifest['file_handlers'] as $id => $handler) {
            if (str_starts_with((string) $handler['action'], '/')) {
                continue;
            }
            if ($this->router === null) {
                $io->error('The router is not available. Unable to generate the file handler action URL.');
                return self::FAILURE;
            }
            $manifest['file_handlers'][$id]['action'] = $this->router->generate(
                $handler['action'],
                [],
                RouterInterface::RELATIVE_PATH
            );
        }

        return $manifest;
    }

    /**
     * @return iterable<string}>
     */
    private function findImages(string $src): iterable
    {
        $finder = new Finder();
        if (is_file($src)) {
            yield $src;
            return;
        }
        $files = $finder->in($src)
            ->files()
            ->name('/\.(png|jpg|jpeg|gif|webp|svg)$/i');
        foreach ($files as $file) {
            if ($file->isFile()) {
                yield $file->getRealPath();
            } else {
                yield from $this->findImages($file->getRealPath());
            }
        }
    }

    private function processServiceWorker(SymfonyStyle $io, array $manifest): int|array
    {
        if (! isset($manifest['serviceworker'])) {
            $io->error('Service worker generation is disabled. Skipping.');
            return $manifest;
        }
        $generate = $manifest['serviceworker']['generate'];
        unset($manifest['serviceworker']['generate']);

        if ($generate !== true) {
            $io->info('Service worker generation is disabled. Skipping.');
            return $manifest;
        }

        $dest = $manifest['serviceworker']['filepath'];
        $scope = $manifest['serviceworker']['scope'];
        $src = $manifest['serviceworker']['src'];
        unset($manifest['serviceworker']['filepath']);

        if (! $this->filesystem->exists(dirname((string) $dest))) {
            $this->filesystem->mkdir(dirname((string) $dest));
        }
        if ($this->filesystem->exists($dest)) {
            $io->info('Service worker already exists. Skipping.');
            return $manifest;
        }

        $resourcePath = $this->fileLocator->locate('@SpomkyLabsPwaBundle/Resources/workbox.js', null, false);
        if (count($resourcePath) !== 1) {
            $io->error('Unable to find the Workbox resource.');
            return self::FAILURE;
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

        return $manifest;
    }
}
