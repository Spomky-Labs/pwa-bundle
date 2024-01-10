<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Panther\Client;

trait ScreenshotsProcessorTrait
{
    use ImageSectionProcessorTrait;

    abstract protected function getWebClient(): ?Client;

    abstract protected function getScreenshotPrefixUrl(): string;

    abstract protected function getScreenshotFolder(): string;

    protected function processScreenshots(SymfonyStyle $io, array $screenshots): array|int
    {
        if (! $this->createDirectoryIfNotExists($this->getScreenshotFolder()) || ! $this->checkImageProcessor(
            $io
        )) {
            return Command::FAILURE;
        }
        $config = [];
        foreach ($screenshots as $screenshot) {
            if (isset($screenshot['src'])) {
                $src = $screenshot['src'];
                if (! $this->getFilesystem()->exists($src)) {
                    continue;
                }
                foreach ($this->findImages($src) as $image) {
                    $data = $screenshot;
                    $data['src'] = $image;
                    $config[] = $data;
                }
            }
            if (isset($screenshot['path'])) {
                if ($this->getWebClient() === null) {
                    $io->error(
                        'The web client is not available. Unable to take a screenshot. Please install "symfony/panther" and a web driver.'
                    );
                    return Command::FAILURE;
                }
                $path = $screenshot['path'];
                $height = $screenshot['height'];
                $width = $screenshot['width'];
                unset($screenshot['path'], $screenshot['height'], $screenshot['width']);

                $client = clone $this->getWebClient();
                $client->request('GET', $path);
                $tmpName = $this->getFilesystem()
                    ->tempnam('', 'pwa-');
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

        $result = [];
        foreach ($config as $screenshot) {
            $data = $this->loadFileAndConvert($screenshot['src'], null, $screenshot['format'] ?? null);
            if ($data === null) {
                $io->error(sprintf('Unable to read the icon "%s"', $screenshot['src']));
                return Command::FAILURE;
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
            $result[] = $screenshotManifest;
            if ($delete) {
                $this->getFilesystem()
                    ->remove($screenshot['src']);
            }
        }

        return $result;
    }

    /**
     * @return array{src: string, type: string, sizes: string, form_factor: ?string}
     */
    private function storeScreenshot(string $data, ?string $format, ?string $formFactor): array
    {
        if ($format !== null) {
            $data = $this->getImageProcessor()
                ->process($data, null, null, $format);
        }

        ['width' => $width, 'height' => $height] = $this->getImageProcessor()->getSizes($data);
        $size = sprintf('%sx%s', $width, $height);

        $fileData = $this->storeFile(
            $data,
            $this->getScreenshotPrefixUrl(),
            $this->getScreenshotFolder(),
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
}
