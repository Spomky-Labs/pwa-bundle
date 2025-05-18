<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\EventListener;

use SpomkyLabs\PwaBundle\Service\FileCompiler;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class FileCompileEventListener
{
    public function __construct(
        private FileCompiler $fileCompiler
    ) {
    }

    #[AsEventListener(PreAssetsCompileEvent::class)]
    public function __invoke(): void
    {
        $this->fileCompiler->compile();
    }
}
