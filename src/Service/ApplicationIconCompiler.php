<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function count;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ApplicationIconCompiler implements FileCompilerInterface
{
    private Manifest $manifest;

    public function __construct(
        private IconResolver $iconResolver,
        ManifestBuilder $manifestBuilder,
        #[Autowire(param: 'kernel.debug')]
        public bool $debug,
    ) {
        $this->manifest = $manifestBuilder->create();
    }

    /**
     * @return iterable<string, Data>
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
        foreach ($this->manifest->iconsLocalized as $localizedIcons) {
            $icons = array_merge($icons, $localizedIcons);
        }
        if (count($this->manifest->shortcuts) !== 0) {
            foreach ($this->manifest->shortcuts as $shortcut) {
                $icons = array_merge($icons, $shortcut->icons);
                foreach ($shortcut->iconsLocalized as $localizedIcons) {
                    $icons = array_merge($icons, $localizedIcons);
                }
            }
        }
        if (count($this->manifest->widgets) !== 0) {
            foreach ($this->manifest->widgets as $widget) {
                $icons = array_merge($icons, $widget->icons);
            }
        }

        foreach ($icons as $icon) {
            $data = $this->iconResolver->getIcon($icon);
            yield $data->url => $data;
        }
    }
}
