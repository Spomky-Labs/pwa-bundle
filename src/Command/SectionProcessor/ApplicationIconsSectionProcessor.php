<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use function is_int;

final class ApplicationIconsSectionProcessor implements SectionProcessor
{
    use IconsSectionProcessorTrait;

    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire('%spomky_labs_pwa.dest%')]
        private readonly array $dest,
        private readonly null|ImageProcessor $imageProcessor = null,
    ) {
    }

    public function process(SymfonyStyle $io, array $config, array $manifest): array|int
    {
        if ($config['icons'] === []) {
            return $manifest;
        }
        $result = $this->processIcons($io, $config['icons']);
        if (is_int($result)) {
            return $result;
        }
        $manifest['icons'] = $result;
        $io->info('Icons are built');

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
