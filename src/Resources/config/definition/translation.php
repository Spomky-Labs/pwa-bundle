<?php

declare(strict_types=1);

use SpomkyLabs\PwaBundle\SpomkyLabsPwaBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
        ->scalarNode('translation_domain')
        ->defaultValue(SpomkyLabsPwaBundle::DEFAULT_TRANSLATION_DOMAIN)
        ->cannotBeEmpty()
        ->validate()
        ->ifTrue(static fn (mixed $value): bool => ! is_string($value))
        ->thenInvalid('The translation domain must be a string, %s given.')
        ->end()
        ->info('The translation domain of the translatable values: manifest members, background fetch messages and startup image templates.')
        ->end()
        ->end();
};
