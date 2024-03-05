<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function getFileHandlersNode(): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('file_handlers');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);

    $node->info(
        'It specifies an array of objects representing the types of files an installed progressive web app (PWA) can handle.'
    )
        ->treatFalseLike([])
        ->treatTrueLike([])
        ->treatNullLike([])
        ->arrayPrototype()
        ->children()
        ->append(getUrlNode('action', 'The action to take.', ['/handle-audio-file']))
        ->arrayNode('accept')
        ->requiresAtLeastOneElement()
        ->useAttributeAsKey('name')
        ->arrayPrototype()
        ->scalarPrototype()
        ->end()
        ->end()
        ->info('The file types that the action will be applied to.')
        ->example('image/*')
        ->end()
        ->end()
        ->end()
        ->end();

    return $node;
}
