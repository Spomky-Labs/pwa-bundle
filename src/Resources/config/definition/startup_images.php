<?php

declare(strict_types=1);

require_once __DIR__ . '/utils/startup_images.php';

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
        ->arrayNode('startup_images')
        ->info(
            'The images iOS shows while the application is starting up. One image is generated per device and orientation.'
        )
        ->addDefaultsIfNotSet()
        ->children()
        ->booleanNode('enabled')
        // Left undecided on purpose: the section then follows "favicons.use_start_image", which used to be
        // the only way to ask for startup images.
        ->defaultNull()
        ->info('Whether startup images are generated. Defaults to the value of "favicons.use_start_image".')
        ->end()
        ->scalarNode('template')
        ->defaultNull()
        ->info(
            'The Twig template describing the image. When omitted, the source image is simply centered over the background color.'
        )
        ->example(['pwa/startup_image.html.twig', '@SpomkyLabsPwa/StartupImage/default.html.twig'])
        ->end()
        ->arrayNode('context')
        ->useAttributeAsKey('name')
        ->info('Variables handed to the template, whatever the color scheme.')
        ->example([
            'subtitle' => 'Your daily companion',
        ])
        ->variablePrototype()
        ->end()
        ->end()
        ->append(
            getStartupImageThemeNode(
                'default',
                'The startup image parameters. When used with "dark", these become the light ones.'
            )
        )
        ->append(getStartupImageThemeNode('dark', 'The startup image parameters for the dark color scheme.'))
        ->end()
        ->end()
        ->end();
};
