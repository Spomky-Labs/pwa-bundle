<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * @param array<string> $examples
 */
function getUrlNode(string $name, string $info, null|array $examples = null): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder($name);
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);
    $node
        ->info($info)
        ->beforeNormalization()
        ->ifString()
        ->then(static fn (string $v): array => [
            'path' => $v,
        ])
        ->end()
        ->children()
        ->scalarNode('path')
        ->isRequired()
        ->info('The URL or route name.')
        ->example($examples ?? ['https://example.com', 'app_action_route', '/do/action'])
        ->end()
        ->arrayNode('params')
        ->treatFalseLike([])
        ->treatTrueLike([])
        ->treatNullLike([])
        ->prototype('variable')
        ->end()
        ->info('The parameters of the action. Only used if the action is a route to a controller.')
        ->end()
        ->end()
        ->end();

    return $node;
}
