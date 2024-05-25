<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function getScreenshotsNode(string $info): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('screenshots');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);
    $node
        ->info($info)
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
        ->info('The path to the screenshot. Can be served by Asset Mapper.')
        ->example('screenshot/lowres.webp')
        ->end()
        ->scalarNode('height')
        ->defaultNull()
        ->example('1080')
        ->end()
        ->scalarNode('width')
        ->defaultNull()
        ->example('1080')
        ->end()
        ->scalarNode('form_factor')
        ->info('The form factor of the screenshot. Will guess the form factor if not set.')
        ->example(['wide', 'narrow'])
        ->end()
        ->scalarNode('label')
        ->info('The label of the screenshot.')
        ->example('Homescreen of Awesome App')
        ->end()
        ->scalarNode('platform')
        ->info('The platform of the screenshot.')
        ->example(['android', 'windows', 'chromeos', 'ipados', 'ios', 'kaios', 'macos', 'windows', 'xbox'])
        ->end()
        ->scalarNode('format')
        ->info('The format of the screenshot. Will convert the file if set.')
        ->example(['jpg', 'png', 'webp'])
        ->end()
        ->scalarNode('reference')
            ->defaultNull()
            ->info('The URL of the screenshot. Only for reference and not used by the bundle.')
        ->end()
        ->end()
        ->end();

    return $node;
}
