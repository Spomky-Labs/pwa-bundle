<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function setupShortcuts(): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('shortcuts');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);
    $node
        ->treatFalseLike([])
        ->treatTrueLike([])
        ->treatNullLike([])
        ->info('The shortcuts of the application.')
        ->arrayPrototype()
        ->children()
        ->scalarNode('name')
        ->isRequired()
        ->info('The name of the shortcut.')
        ->example('Awesome shortcut')
        ->end()
        ->scalarNode('short_name')
        ->info('The short name of the shortcut.')
        ->example('shortcut')
        ->end()
        ->scalarNode('description')
        ->info('The description of the shortcut.')
        ->example('This is an awesome shortcut')
        ->end()
        ->append(getUrlNode('url', 'The URL of the shortcut.'))
        ->append(getIconsNode('The icons of the shortcut.'))
        ->end()
        ->end()
        ->end();

    return $node;
}
