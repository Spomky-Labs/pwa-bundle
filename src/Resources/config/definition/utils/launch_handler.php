<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function getLaunchHandlerNode(): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('launch_handler');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);

    $node->info('The launch handler of the application.')
        ->treatFalseLike([])
        ->treatTrueLike([])
        ->treatNullLike([])
        ->children()
        ->arrayNode('client_mode')
        ->info('The client mode of the application.')
        ->example(['focus-existing', 'auto'])
        ->scalarPrototype()
        ->end()
        ->beforeNormalization()
        ->castToArray()
        ->end()
        ->end()
        ->end()
        ->end();

    return $node;
}
