<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use Facebook\WebDriver\WebDriverDimension;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\Client;
use Symfony\Component\Routing\RouterInterface;

final class Windows10WidgetsSectionProcessor implements SectionProcessor
{
    use ScreenshotsProcessorTrait;
    use IconsSectionProcessorTrait;

    private readonly null|Client $webClient;

    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire('%spomky_labs_pwa.dest%')]
        private readonly array $dest,
        #[Autowire('@pwa.web_client')]
        null|Client $webClient,
        private readonly null|ImageProcessor $imageProcessor = null,
        private readonly null|RouterInterface $router = null,
    ) {
        if ($webClient === null && class_exists(Client::class) && class_exists(WebDriverDimension::class)) {
            $webClient = Client::createChromeClient();
        }
        $this->webClient = $webClient;
    }

    public function process(SymfonyStyle $io, array $config, array $manifest): array|int
    {
        if ($config['widgets'] === []) {
            return $manifest;
        }
        $manifest['widgets'] = [];
        foreach ($config['widgets'] as $widget) {
            if (isset($widget['icons'])) {
                $widget['icons'] = $this->processIcons($io, $widget['icons']);
            }
            if (isset($widget['screenshots'])) {
                $widget['screenshots'] = $this->processScreenshots($io, $widget['screenshots']);
            }
            $manifest['widgets'][] = $widget;
        }

        return $manifest;
    }

    protected function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    protected function getIconPrefixUrl(): string
    {
        return $this->dest['icon_prefix_url'];
    }

    protected function getIconFolder(): string
    {
        return $this->dest['icon_folder'];
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
