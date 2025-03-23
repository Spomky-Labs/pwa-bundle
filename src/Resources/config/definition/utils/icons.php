<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function expandIcons(array $icons): array
{
    $expandedIcons = [];
    foreach ($icons as $icon) {
        if (! is_array($icon)) {
            $icon = [
                'src' => $icon,
            ];
        }
        if (! array_key_exists('sizes', $icon) || ! is_array($icon['sizes'])) {
            $expandedIcons[] = $icon;
            continue;
        }

        foreach ($icon['sizes'] as $size) {
            $expandedIcon = $icon;
            $expandedIcon['sizes'] = [$size];
            $expandedIcons[] = $expandedIcon;
        }
    }

    return $expandedIcons;
}

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
            ->scalarNode('background_color')
                ->defaultNull()
                ->info(
                    'The background color of the application. If this value is not defined and that of the Manifest section is, the value of the latter will be used.'
                )
                ->example(['red', '#f5ef06'])
            ->end()
            ->integerNode('border_radius')
                ->defaultNull()
                ->min(1)
                ->max(50)
                ->info('The border radius of the icon.')
            ->end()
            ->integerNode('image_scale')
                ->defaultNull()
                ->min(1)
                ->max(100)
                ->info('The scale of the icon.')
            ->end()
            ->scalarNode('type')
                ->info('The icon mime type.')
                ->example(['image/webp', 'image/png'])
            ->end()
            ->scalarNode('format')
                ->info('The icon format. When set, the "type" option is ignored and the image will be converted.')
                ->example(['image/webp', 'image/png'])
            ->end()
            ->scalarNode('purpose')
                ->info('The purpose of the icon.')
                ->example(['any', 'maskable', 'monochrome'])
            ->end()
        ->end()
    ->end();

    return $node;
}
