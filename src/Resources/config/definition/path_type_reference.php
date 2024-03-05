<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->integerNode('path_type_reference')
                ->setDeprecated(
                    'spomky-labs/phpwa',
                    '1.1.0',
                    'The "%node%" configuration key is deprecated. Use the "path_type_reference" of URL nodes instead.'
                )
                ->defaultValue(UrlGeneratorInterface::ABSOLUTE_PATH)
                ->info(
                    'The path type reference to generate paths/URLs. See https://symfony.com/doc/current/routing.html#generating-urls-in-controllers for more information.'
                )
                ->example([
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    UrlGeneratorInterface::ABSOLUTE_URL,
                    UrlGeneratorInterface::NETWORK_PATH,
                    UrlGeneratorInterface::RELATIVE_PATH,
                ])
                ->validate()
                ->ifNotInArray([
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    UrlGeneratorInterface::ABSOLUTE_URL,
                    UrlGeneratorInterface::NETWORK_PATH,
                    UrlGeneratorInterface::RELATIVE_PATH,
                ])
                ->thenInvalid('Invalid path type reference "%s".')
            ->end()
        ->end();
};
