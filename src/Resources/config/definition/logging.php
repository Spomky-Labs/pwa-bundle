<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->scalarNode('logger')
                ->defaultNull()
                ->info('The logger service to use. If not set, the default logger will be used.')
            ->end()
        ->end();
};
