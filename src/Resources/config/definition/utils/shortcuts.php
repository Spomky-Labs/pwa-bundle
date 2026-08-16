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
        ->beforeNormalization()
        ->ifTrue(static fn (mixed $v): bool => is_array($v) && is_array($v['icons'] ?? null))
        ->then(static function (array $v): array {
            $icons = $v['icons'];
            assert(is_array($icons));
            $v['icons'] = expandIcons($icons);
            return $v;
        })
        ->end()
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
        ->append(getLocalizedIconsNode('The icons of the shortcut, per locale.'))
        ->end()
        ->end();

    return $node;
}
