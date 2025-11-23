<?php

declare(strict_types=1);

use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
        ->scalarNode('image_processor')
        ->beforeNormalization()
        ->ifTrue(static fn ($v): bool => is_string($v) && in_array(strtolower($v), ['imagick', 'gd'], true))
        ->then(static fn (string $v): string => sprintf('pwa.image_processor.%s', strtolower($v)))
        ->end()
        ->defaultNull()
        ->info('The image processor to use to generate the icons of different sizes.')
        ->example(GDImageProcessor::class)
        ->end()
        ->end();
};
