<?php

declare(strict_types=1);

require_once __DIR__ . '/utils/icons.php';

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
        ->arrayNode('favicons')
        ->canBeEnabled()
        ->validate()
        ->ifTrue(static fn (array $v): bool => ! isset($v['default']))
        ->thenInvalid('The default theme favicon shall be defined.')
        ->end()
        ->beforeNormalization()
        ->ifTrue(static fn (mixed $v): bool => true)
        ->then(static function (mixed $v): mixed {
            if (! is_array($v)) {
                return $v;
            }
            if (isset($v['src'])) {
                $v['default'] = [
                    'src' => $v['src'],
                    'background_color' => $v['background_color'] ?? null,
                    'border_radius' => $v['border_radius'] ?? null,
                    'image_scale' => $v['image_scale'] ?? null,
                    'svg_attr' => [
                        'color' => $v['svg_color'] ?? null,
                    ],
                ];
                $v['default'] = array_filter($v['default'], static fn ($v): bool => $v !== null);
                $v['default']['svg_attr'] = array_filter(
                    $v['default']['svg_attr'],
                    static fn ($v): bool => $v !== null
                );
            }
            if (isset($v['src_dark'])) {
                $v['dark'] = [
                    'src' => $v['src_dark'],
                    'background_color' => $v['background_color_dark'] ?? null,
                    'border_radius' => $v['border_radius'] ?? null,
                    'image_scale' => $v['image_scale'] ?? null,
                    'svg_attr' => [
                        'color' => $v['svg_color'] ?? null,
                    ],
                ];
                $v['dark'] = array_filter($v['dark'], static fn ($v): bool => $v !== null);
                $v['dark']['svg_attr'] = array_filter($v['dark']['svg_attr'], static fn ($v): bool => $v !== null);
            }
            foreach ([
                'src',
                'background_color',
                'src_dark',
                'background_color_dark',
                'border_radius',
                'image_scale',
                'svg_color',
            ] as $key) {
                if (array_key_exists($key, $v)) {
                    unset($v[$key]);
                }
            }

            return $v;
        })
        ->end()
        ->children()
        ->append(
            getFaviconNode(
                'default',
                'The favicon source and parameters. When used with "dark", this favicon will become the light version.'
            )
        )
        ->append(
            getFaviconNode(
                'dark',
                'The favicon source and parameters for the dark theme. Should only be used with "default".'
            )
        )
        ->scalarNode('src')
        ->defaultNull()
        ->setDeprecated(
            'spomky-labs/phpwa',
            '1.3.0',
            'The "%node%" configuration key is deprecated. Use the "default.src" configuration key instead.'
        )
        ->info('The source of the favicon. Shall be a SVG or large PNG.')
        ->end()
        ->scalarNode('src_dark')
        ->defaultNull()
        ->setDeprecated(
            'spomky-labs/phpwa',
            '1.3.0',
            'The "%node%" configuration key is deprecated. Use the "dark.src" configuration key instead.'
        )
        ->info('The source of the favicon in dark mode. Shall be a SVG or large PNG.')
        ->end()
        ->scalarNode('background_color')
        ->defaultNull()
        ->setDeprecated(
            'spomky-labs/phpwa',
            '1.3.0',
            'The "%node%" configuration key is deprecated. Use the "default.background_color" configuration key instead.'
        )
        ->info('The background color of the icon.')
        ->example(['red', '#f5ef06'])
        ->end()
        ->scalarNode('background_color_dark')
        ->defaultNull()
        ->setDeprecated(
            'spomky-labs/phpwa',
            '1.3.0',
            'The "%node%" configuration key is deprecated. Use the "dark.background_color" configuration key instead.'
        )
        ->info('The background color of the icon in dark mode.')
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
        ->setDeprecated(
            'spomky-labs/phpwa',
            '1.3.0',
            'The "%node%" configuration key is deprecated. Use the "default.border_radius" or "dark.border_radius" configuration key instead.'
        )
        ->min(1)
        ->max(50)
        ->info('The border radius of the icon.')
        ->end()
        ->integerNode('image_scale')
        ->defaultNull()
        ->setDeprecated(
            'spomky-labs/phpwa',
            '1.3.0',
            'The "%node%" configuration key is deprecated. Use the "default.image_scale" or "dark.image_scale" configuration key instead.'
        )
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
        ->booleanNode('use_start_image')
        ->defaultTrue()
        ->info('Use the icon as a start image for the iOS splash screen.')
        ->end()
        ->scalarNode('svg_color')
        ->defaultValue('#000')
        ->info('When the asset is a SVG file, replaces the currentColor attribute with this color.')
        ->end()
        ->booleanNode('monochrome')
        ->defaultFalse()
        ->info('Use a monochrome icon.')
        ->end()
        ->scalarNode('potrace')
        ->defaultValue('potrace')
        ->info('The path to the potrace binary.')
        ->end()
        ->end()
        ->end();
};
