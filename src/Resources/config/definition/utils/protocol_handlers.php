<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function getProtocolHandlersNode(): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('protocol_handlers');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);

    $node->info('The protocol handlers of the application.')
        ->treatFalseLike([])
        ->treatTrueLike([])
        ->treatNullLike([])
        ->arrayPrototype()
        ->children()
        ->scalarNode('protocol')
        ->isRequired()
        ->info('The protocol of the handler.')
        ->example('web+jngl')
        ->end()
        ->scalarNode('placeholder')
        ->defaultNull()
        ->info('The placeholder of the handler. Will be replaced by "xxx=%s".')
        ->example('type')
        ->end()
        ->append(getUrlNode('url', 'The URL of the handler.'))
        ->end()
        ->end()
        ->end();

    return $node;
}
