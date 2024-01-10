<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;
use Symfony\Component\Console\Style\SymfonyStyle;

trait ImageSectionProcessorTrait
{
    use FileProcessorTrait;

    abstract protected function getImageProcessor(): ?ImageProcessor;

    protected function handleSizeAndPurpose(?string $purpose, int $size, array $fileData): array
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

    protected function loadFileAndConvert(string $src, ?int $size, ?string $format): ?string
    {
        $data = file_get_contents($src);
        if ($data === false) {
            return null;
        }
        if ($size !== 0 && $size !== null) {
            $data = $this->getImageProcessor()
                ->process($data, $size, $size, $format);
        }

        return $data;
    }

    protected function checkImageProcessor(SymfonyStyle $io): bool
    {
        if ($this->getImageProcessor() === null) {
            $io->error('Image processor not found');
            return false;
        }

        return true;
    }
}
