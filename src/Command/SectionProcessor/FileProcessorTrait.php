<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;

trait FileProcessorTrait
{
    private null|MimeTypes $mime = null;

    abstract protected function getFilesystem(): Filesystem;

    protected function getMime(): MimeTypes
    {
        if (! isset($this->mime)) {
            $this->mime = MimeTypes::getDefault();
        }
        return $this->mime;
    }

    protected function createDirectoryIfNotExists(string $folder): bool
    {
        try {
            if (! $this->getFilesystem()->exists($folder)) {
                $this->getFilesystem()
                    ->mkdir($folder);
            }
        } catch (IOExceptionInterface) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string|null> $components
     * @return array{src: string, type: string}
     */
    protected function storeFile(string $data, string $prefixUrl, string $storageFolder, array $components): array
    {
        $tempFilename = $this->getFilesystem()
            ->tempnam($storageFolder, 'pwa-');
        $hash = mb_substr(hash('sha256', $data), 0, 8);
        file_put_contents($tempFilename, $data);
        $mime = $this->getMime()
            ->guessMimeType($tempFilename);
        $extension = $this->getMime()
            ->getExtensions($mime);

        if (empty($extension)) {
            throw new RuntimeException(sprintf('Unable to guess the extension for the mime type "%s"', $mime));
        }

        $components[] = $hash;
        $filename = sprintf('%s.%s', implode('-', $components), $extension[0]);
        $localFilename = sprintf('%s/%s', rtrim($storageFolder, '/'), $filename);

        file_put_contents($localFilename, $data);
        $this->getFilesystem()
            ->remove($tempFilename);

        return [
            'src' => sprintf('%s/%s', $prefixUrl, $filename),
            'type' => $mime,
        ];
    }
}
