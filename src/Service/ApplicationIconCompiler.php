<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function count;

final readonly class ApplicationIconCompiler implements FileCompilerInterface
{
    public function __construct(
        private IconResolver $iconResolver,
        private Manifest $manifest,
        #[Autowire(param: 'kernel.debug')]
        public bool $debug,
    ) {
    }

    /**
     * @return iterable<Data>
     */
    public function getFiles(): iterable
    {
        $icons = [];
        if ($this->manifest->enabled === false) {
            yield from $icons;
            return;
        }
        if (count($this->manifest->icons) !== 0) {
            $icons = array_merge($icons, $this->manifest->icons);
        }
        if (count($this->manifest->shortcuts) !== 0) {
            foreach ($this->manifest->shortcuts as $shortcut) {
                $icons = array_merge($icons, $shortcut->icons);
            }
        }
        if (count($this->manifest->widgets) !== 0) {
            foreach ($this->manifest->widgets as $widget) {
                $icons = array_merge($icons, $widget->icons);
            }
        }

        foreach ($icons as $icon) {
            yield $this->iconResolver->getIcon($icon);
        }
    }
}
