<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use function is_int;

trait IconsSectionProcessorTrait
{
    use ImageSectionProcessorTrait;

    abstract protected function getIconPrefixUrl(): string;

    abstract protected function getIconFolder(): string;

    /**
     * @param array{src: string, sizes: array<int>, format: ?string, purpose: ?string} $icons
     */
    protected function processIcons(SymfonyStyle $io, array $icons): array|int
    {
        if (! $this->createDirectoryIfNotExists($this->getIconFolder()) || ! $this->checkImageProcessor($io)) {
            return Command::FAILURE;
        }
        $result = [];
        foreach ($icons as $icon) {
            foreach ($icon['sizes'] as $size) {
                if (! is_int($size) || $size < 0) {
                    $io->error('The icon size must be a positive integer');
                    return Command::FAILURE;
                }
                $data = $this->loadFileAndConvert($icon['src'], $size, $icon['format'] ?? null);
                if ($data === null) {
                    $io->error(sprintf('Unable to read the icon "%s"', $icon['src']));
                    return Command::FAILURE;
                }

                $iconManifest = $this->storeIcon($data, $size, $icon['purpose'] ?? null);
                $result[] = $iconManifest;
            }
        }

        return $result;
    }

    /**
     * @return array{src: string, sizes: string, type: string, purpose: ?string}
     */
    private function storeIcon(string $data, int $size, ?string $purpose): array
    {
        $fileData = $this->storeFile(
            $data,
            $this->getIconPrefixUrl(),
            $this->getIconFolder(),
            ['icon', $purpose, $size === 0 ? 'any' : $size . 'x' . $size]
        );

        return $this->handleSizeAndPurpose($purpose, $size, $fileData);
    }
}
