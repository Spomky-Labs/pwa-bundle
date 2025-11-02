<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\EventListener;

use SpomkyLabs\PwaBundle\Service\FileCompiler;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use function assert;

final readonly class FileCompileEventListener
{
    public function __construct(
        private FileCompiler $fileCompiler,
        #[Autowire(param: 'spomky_labs_pwa.asset_compiler')]
        private bool $enabled = true,
    ) {
    }

    #[AsEventListener(PreAssetsCompileEvent::class)]
    public function __invoke(PreAssetsCompileEvent $event): void
    {
        assert($event instanceof PreAssetsCompileEvent);
        if (! $this->enabled) {
            return;
        }
        $this->fileCompiler->compile();
    }
}
