<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->arrayNode('favicons')
                ->canBeEnabled()
                ->children()
                    ->scalarNode('src')
                        ->isRequired()
                        ->info('The source of the favicon. Shall be a SVG or large PNG.')
                    ->end()
                    ->scalarNode('background_color')
                        ->defaultNull()
                        ->info(
                            'The background color of the application. If this value is not defined and that of the Manifest section is, the value of the latter will be used.'
                        )
                        ->example(['red', '#f5ef06'])
                    ->end()
                    ->scalarNode('safari_pinned_tab_color')
                        ->defaultNull()
                        ->info('The color of the Safari pinned tab. Requires "use_silhouette" to be set to "true".')
                        ->example(['red', '#f5ef06'])
                    ->end()
                    ->scalarNode('tile_color')
                        ->defaultNull()
                        ->info('The color of the tile for Windows 8+.')
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
                    ->booleanNode('low_resolution')
                        ->defaultFalse()
                        ->info('Include low resolution icons.')
                    ->end()
                    ->booleanNode('use_silhouette')
                        ->defaultNull()
                        ->info(
                            'Use only the silhouette of the icon. Applicable for macOS Safari and Windows 8+. Requires potrace to be installed.'
                        )
                    ->end()
                    ->scalarNode('potrace')
                        ->defaultValue('potrace')
                        ->info('The path to the potrace binary.')
                    ->end()
                ->end()
            ->end()
        ->end()
    ->end();
};
