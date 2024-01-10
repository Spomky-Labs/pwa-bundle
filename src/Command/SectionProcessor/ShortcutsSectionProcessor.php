<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RouterInterface;

final class ShortcutsSectionProcessor implements SectionProcessor
{
    use IconsSectionProcessorTrait;

    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire('%spomky_labs_pwa.dest%')]
        private readonly array $dest,
        private readonly null|ImageProcessor $imageProcessor = null,
        private readonly null|RouterInterface $router = null,
    ) {
    }

    public function process(SymfonyStyle $io, array $config, array $manifest): array|int
    {
        if ($config['shortcuts'] === []) {
            return $manifest;
        }
        $manifest['shortcuts'] = [];
        foreach ($config['shortcuts'] as $shortcutConfig) {
            $shortcut = $shortcutConfig;
            if (isset($shortcut['icons'])) {
                unset($shortcut['icons']);
            }
            if (! str_starts_with((string) $shortcut['url'], '/')) {
                if ($this->router === null) {
                    $io->error('The router is not available');
                    return Command::FAILURE;
                }
                $shortcut['url'] = $this->router->generate($shortcut['url'], [], RouterInterface::RELATIVE_PATH);
            }

            if (isset($shortcutConfig['icons'])) {
                $shortcut['icons'] = $this->processIcons($io, $shortcutConfig['icons']);
            }
            $manifest['shortcuts'][] = $shortcut;
        }
        $manifest['shortcuts'] = array_values($manifest['shortcuts']);

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
}
