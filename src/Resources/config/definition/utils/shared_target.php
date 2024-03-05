<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function setupSharedTarget(): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('share_target');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);

    $node
        ->treatFalseLike([])
        ->treatTrueLike([])
        ->treatNullLike([])
        ->info('The share target of the application.')
        ->children()
        ->append(getUrlNode('action', 'The action of the share target.', ['/shared-content-receiver/']))
        ->scalarNode('method')
        ->info('The method of the share target.')
        ->example('GET')
        ->end()
        ->scalarNode('enctype')
        ->info('The enctype of the share target. Ignored if method is GET.')
        ->example('multipart/form-data')
        ->end()
        ->arrayNode('params')
        ->isRequired()
        ->info('The parameters of the share target.')
        ->children()
        ->scalarNode('title')
        ->info('The title of the share target.')
        ->example('name')
        ->end()
        ->scalarNode('text')
        ->info('The text of the share target.')
        ->example('description')
        ->end()
        ->scalarNode('url')
        ->info('The URL of the share target.')
        ->example('link')
        ->end()
        ->arrayNode('files')
        ->info('The files of the share target.')
        ->scalarPrototype()
        ->end()
        ->end()
        ->end()
        ->end()
        ->end()
        ->end();

    return $node;
}
