<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function getIconsNode(string $info): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('icons');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);
    $node->info($info)
        ->treatFalseLike([])
        ->treatTrueLike([])
        ->treatNullLike([])
        ->arrayPrototype()
        ->beforeNormalization()
        ->ifString()
        ->then(static fn (string $v): array => [
            'src' => $v,
        ])
        ->end()
        ->children()
        ->scalarNode('src')
        ->isRequired()
        ->info('The path to the icon. Can be served by Asset Mapper.')
        ->example('icon/logo.svg')
        ->end()
        ->arrayNode('sizes')
        ->beforeNormalization()
        ->ifTrue(static fn (mixed $v): bool => is_int($v))
        ->then(static fn (int $v): array => [$v])
        ->end()
        ->beforeNormalization()
        ->ifTrue(static fn (mixed $v): bool => is_string($v))
        ->then(static function (string $v): array {
            if ($v === 'any') {
                return [0];
            }

            return [(int) $v];
        })
        ->end()
        ->info(
            'The sizes of the icon. 16 means 16x16, 32 means 32x32, etc. 0 means "any" (i.e. it is a vector image).'
        )
        ->example([['16', '32']])
        ->integerPrototype()
        ->end()
        ->end()
        ->scalarNode('type')
        ->info('The icon mime type.')
        ->example(['image/webp', 'image/png'])
        ->end()
        ->scalarNode('purpose')
        ->info('The purpose of the icon.')
        ->example(['any', 'maskable', 'monochrome'])
        ->end()
        ->end()
        ->end()
    ;

    return $node;
}
