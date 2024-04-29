<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->beforeNormalization()
            ->ifTrue(
                static fn (null|array $v): bool => $v !== null && isset($v['manifest']) && $v['manifest']['enabled'] === true && isset($v['favicons']) && $v['favicons']['enabled'] === true && isset($v['manifest']['theme_color'])
            )
            ->then(static function (array $v): array {
                $v['favicons']['background_color'] = $v['manifest']['theme_color'];
                return $v;
            })
        ->end()
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
                        ->info('The color of the Safari pinned tab.')
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
                    ->booleanNode('generate_precomposed')
                        ->defaultFalse()
                        ->info('Generate precomposed icons. Useful for old iOS devices.')
                    ->end()
                    ->booleanNode('only_high_resolution')
                        ->defaultTrue()
                        ->info('Only high resolution icons.')
                    ->end()
                    ->booleanNode('only_tile_silhouette')
                        ->defaultTrue()
                        ->info('Only tile silhouette for Windows 8+.')
                    ->end()
                ->end()
            ->end()
        ->end()
    ->end();
};
