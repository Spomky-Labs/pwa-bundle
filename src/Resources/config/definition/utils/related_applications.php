<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function setupRelatedApplications(): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('related_applications');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);
    $node
        ->treatFalseLike([])
        ->treatTrueLike([])
        ->treatNullLike([])
        ->info('The related applications of the application.')
        ->arrayPrototype()
        ->children()
        ->scalarNode('platform')
        ->isRequired()
        ->info('The platform of the application.')
        ->example('play')
        ->end()
        ->append(
            getUrlNode('url', 'The URL of the application.', [
                'https://play.google.com/store/apps/details?id=com.example.app1',
            ])
        )
        ->scalarNode('id')
        ->info('The ID of the application.')
        ->example('com.example.app1')
        ->end()
        ->end()
        ->end()
        ->end();

    return $node;
}
