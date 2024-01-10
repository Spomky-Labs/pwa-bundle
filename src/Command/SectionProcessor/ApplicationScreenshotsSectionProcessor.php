<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use Facebook\WebDriver\WebDriverDimension;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\Client;
use function is_int;

final class ApplicationScreenshotsSectionProcessor implements SectionProcessor
{
    use ScreenshotsProcessorTrait;

    private readonly null|Client $webClient;

    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire('%spomky_labs_pwa.dest%')]
        private readonly array $dest,
        #[Autowire('@pwa.web_client')]
        null|Client $webClient = null,
        private readonly null|ImageProcessor $imageProcessor = null,
    ) {
        if ($webClient === null && class_exists(Client::class) && class_exists(WebDriverDimension::class)) {
            $webClient = Client::createChromeClient();
        }
        $this->webClient = $webClient;
    }

    public function process(SymfonyStyle $io, array $config, array $manifest): array|int
    {
        if ($config['screenshots'] === []) {
            return $manifest;
        }
        $result = $this->processScreenshots($io, $config['screenshots']);
        if (is_int($result)) {
            return $result;
        }
        $manifest['screenshots'] = $result;

        return $manifest;
    }

    protected function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    protected function getImageProcessor(): ?ImageProcessor
    {
        return $this->imageProcessor;
    }

    protected function getWebClient(): ?Client
    {
        return $this->webClient;
    }

    protected function getScreenshotPrefixUrl(): string
    {
        return $this->dest['screenshot_prefix_url'];
    }

    protected function getScreenshotFolder(): string
    {
        return $this->dest['screenshot_folder'];
    }
}
