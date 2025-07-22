<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
        ->booleanNode('asset_compiler')
        ->defaultTrue()
        ->info('When true, the assets will be compiled when the command "asset-map:compile" is run.')
        ->end()
        ->end();
};
