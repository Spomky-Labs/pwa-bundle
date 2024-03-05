<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

function setupWidgets(): ArrayNodeDefinition
{
    $treeBuilder = new TreeBuilder('widgets');
    $node = $treeBuilder->getRootNode();
    assert($node instanceof ArrayNodeDefinition);
    $node
        ->info(
            'EXPERIMENTAL. Specifies PWA-driven widgets. See https://learn.microsoft.com/en-us/microsoft-edge/progressive-web-apps-chromium/how-to/widgets for more information'
        )
        ->arrayPrototype()
        ->children()
        ->scalarNode('name')
        ->isRequired()
        ->info('The title of the widget, presented to users.')
        ->end()
        ->scalarNode('short_name')
        ->info('An alternative short version of the name.')
        ->end()
        ->scalarNode('description')
        ->isRequired()
        ->info('The description of the widget.')
        ->example('My awesome widget')
        ->end()
        ->append(
            getIconsNode(
                'An array of icons to be used for the widget. If missing, the icons manifest member is used instead. Icons larger than 1024x1024 are ignored.'
            )
        )
        ->append(getScreenshotsNode('The screenshots of the widget') ->requiresAtLeastOneElement())
        ->scalarNode('tag')
        ->isRequired()
        ->info('A string used to reference the widget in the PWA service worker.')
        ->end()
        ->scalarNode('template')
        ->info(
            'The template to use to display the widget in the operating system widgets dashboard. Note: this property is currently only informational and not used. See ms_ac_template below.'
        )
        ->end()
        ->append(
            getUrlNode(
                'ms_ac_template',
                'The URL of the custom Adaptive Cards template to use to display the widget in the operating system widgets dashboard.'
            )
        )
        ->append(
            getUrlNode(
                'data',
                'The URL where the data to fill the template with can be found. If present, this URL is required to return valid JSON.'
            )
        )
        ->scalarNode('type')
        ->info('The MIME type for the widget data.')
        ->end()
        ->booleanNode('auth')
        ->info('A boolean indicating if the widget requires authentication.')
        ->end()
        ->integerNode('update')
        ->info(
            'The frequency, in seconds, at which the widget will be updated. Code in your service worker must perform the updating; the widget is not updated automatically. See Access widget instances at runtime.'
        )
        ->end()
        ->booleanNode('multiple')
        ->defaultTrue()
        ->info('A boolean indicating whether to allow multiple instances of the widget. Defaults to true.')
        ->end()
        ->end()
        ->end()
        ->end();

    return $node;
}
