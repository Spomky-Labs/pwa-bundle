<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->scalarNode('asset_public_prefix')
                ->cannotBeOverwritten()
                ->defaultNull()
                ->info('The public prefix of the assets. Shall be the same as the one used in the asset mapper.')
            ->end()
        ->end();
};
