<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function getStartupImageThemeNode(string $nodeName, string $info): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder($nodeName);
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);
    $node
        ->info($info)
        ->addDefaultsIfNotSet()
        ->children()
        ->scalarNode('src')
        ->defaultNull()
        ->info(
            'The path to the image. Can be served by Asset Mapper, an absolute path or a Symfony UX Icon (if the bundle is installed). Falls back to the favicon of the same color scheme.'
        )
        ->example(['icon/logo.svg', '/path/to/my/logo.png', 'logos:pwa'])
        ->end()
        ->scalarNode('background_color')
        ->defaultNull()
        ->info(
            'The background color of the startup image. Falls back to the favicon one, then to the one declared in the Manifest section.'
        )
        ->example(['red', '#f5ef06'])
        ->end()
        ->integerNode('border_radius')
        ->defaultNull()
        ->min(1)
        ->max(50)
        ->info('The border radius of the image. Ignored when a template is used.')
        ->end()
        ->integerNode('image_scale')
        ->defaultNull()
        ->min(1)
        ->max(100)
        ->info(
            'The scale of the image. Ignored when a template is used: the template is free to size the image as it sees fit.'
        )
        ->end()
        ->arrayNode('svg_attr')
        ->useAttributeAsKey('name')
        ->info('Additional attributes to put to the SVG root.')
        ->variablePrototype()
        ->end()
        ->end()
        ->arrayNode('context')
        ->useAttributeAsKey('name')
        ->info('Variables handed to the template for this color scheme only. Merged over the shared ones.')
        ->example([
            'subtitle' => 'The dark side of the application',
        ])
        ->variablePrototype()
        ->end()
        ->end()
        ->end()
        ->end();

    return $node;
}
