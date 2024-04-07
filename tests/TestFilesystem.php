<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests;

use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function dirname;

final readonly class TestFilesystem implements PublicAssetsFilesystemInterface
{
    private string $output;

    public function __construct(
        #[Autowire('%kernel.cache_dir%')]
        string $cacheDir,
    ) {
        $this->output = sprintf('%s/output', $cacheDir);
    }

    public function write(string $path, string $contents): void
    {
        $dest = sprintf('%s/%s', $this->output, $path);
        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }
        file_put_contents($dest, $contents);
    }

    public function copy(string $originPath, string $path): void
    {
        $dest = sprintf('%s/%s', $this->output, $path);
        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }
        copy($originPath, $dest);
    }

    public function getDestinationPath(): string
    {
        return $this->output;
    }
}
