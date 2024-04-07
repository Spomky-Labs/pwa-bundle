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
            ->scalarNode('user_agent')
                ->defaultNull()
                ->info(
                    'The user agent to use when generating screenshots. If not set, the default user agent will be used. When requesting the current application in an environment other than "prod", the profiler will be disabled.'
                )
            ->end()
        ->end();
};
