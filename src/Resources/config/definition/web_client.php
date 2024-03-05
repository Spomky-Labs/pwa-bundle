<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->scalarNode('web_client')
                ->defaultNull()
                ->info('The Panther Client for generating screenshots. If not set, the default client will be used.')
            ->end()
        ->end();
};
