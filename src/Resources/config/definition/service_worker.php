<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->arrayNode('serviceworker')
                ->canBeEnabled()
                ->beforeNormalization()
                ->ifString()
                ->then(static fn (string $v): array => [
                    'enabled' => true,
                    'src' => $v,
                ])
            ->end()
            ->children()
                ->scalarNode('src')
                    ->isRequired()
                    ->info('The path to the service worker source file. Can be served by Asset Mapper.')
                    ->example('script/sw.js')
                ->end()
                ->scalarNode('dest')
                    ->cannotBeEmpty()
                    ->defaultValue('/sw.js')
                    ->info('The public URL to the service worker.')
                    ->example('/sw.js')
                ->end()
                ->booleanNode('skip_waiting')
                    ->defaultFalse()
                    ->info('Whether to skip waiting for the service worker to be activated.')
                ->end()
                ->arrayNode('workbox')
                    ->info('The configuration of the workbox.')
                    ->canBeDisabled()
                    ->children()
                        ->booleanNode('use_cdn')
                            ->defaultFalse()
                            ->info('Whether to use the local workbox or the CDN.')
                        ->end()
                        ->booleanNode('cache_manifest')
                            ->defaultTrue()
                            ->info('Whether to cache the manifest file.')
                        ->end()
                        ->scalarNode('version')
                            ->defaultValue('7.0.0')
                            ->info('The version of workbox. When using local files, the version shall be "7.0.0."')
                        ->end()
                        ->scalarNode('workbox_public_url')
                            ->defaultValue('/workbox')
                            ->info('The public path to the local workbox. Only used if use_cdn is false.')
                        ->end()
                        ->scalarNode('workbox_import_placeholder')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. No replacement.'
                            )
                            ->defaultValue('//WORKBOX_IMPORT_PLACEHOLDER')
                            ->info('The placeholder for the workbox import. Will be replaced by the workbox import.')
                            ->example('//WORKBOX_IMPORT_PLACEHOLDER')
                        ->end()
                        ->scalarNode('standard_rules_placeholder')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. No replacement.'
                            )
                            ->defaultValue('//STANDARD_RULES_PLACEHOLDER')
                            ->info('The placeholder for the standard rules. Will be replaced by caching strategies.')
                            ->example('//STANDARD_RULES_PLACEHOLDER')
                        ->end()
                        ->scalarNode('offline_fallback_placeholder')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. No replacement.'
                            )
                            ->defaultValue('//OFFLINE_FALLBACK_PLACEHOLDER')
                            ->info('The placeholder for the offline fallback. Will be replaced by the URL.')
                            ->example('//OFFLINE_FALLBACK_PLACEHOLDER')
                        ->end()
                        ->scalarNode('widgets_placeholder')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. No replacement.'
                            )
                            ->defaultValue('//WIDGETS_PLACEHOLDER')
                            ->info(
                                'The placeholder for the widgets. Will be replaced by the widgets management events.'
                            )
                            ->example('//WIDGETS_PLACEHOLDER')
                        ->end()
                        ->booleanNode('clear_cache')
                            ->defaultTrue()
                            ->info('Whether to clear the cache during the service worker activation.')
                        ->end()
                        ->scalarNode('image_cache_name')
                            ->defaultValue('images')
                            ->info('The name of the image cache.')
                        ->end()
                        ->scalarNode('font_cache_name')
                            ->defaultValue('fonts')
                            ->info('The name of the font cache.')
                        ->end()
                        ->scalarNode('page_cache_name')
                            ->defaultValue('pages')
                            ->info('The name of the page cache.')
                        ->end()
                        ->scalarNode('asset_cache_name')
                            ->defaultValue('assets')
                            ->info('The name of the asset cache.')
                        ->end()
                        ->append(getUrlNode('page_fallback', 'The URL of the offline page fallback.'))
                        ->append(getUrlNode('image_fallback', 'The URL of the offline image fallback.'))
                        ->append(getUrlNode('font_fallback', 'The URL of the offline font fallback.'))
                        ->scalarNode('image_regex')
                            ->defaultValue('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                            ->info('The regex to match the images.')
                            ->example('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                        ->end()
                        ->scalarNode('static_regex')
                            ->defaultValue('/\.(css|js|json|xml|txt|map|webmanifest)$/')
                            ->info('The regex to match the static files.')
                            ->example('/\.(css|js|json|xml|txt|woff2|ttf|eot|otf|map|webmanifest)$/')
                        ->end()
                        ->scalarNode('font_regex')
                            ->defaultValue('/\.(ttf|eot|otf|woff2)$/')
                            ->info('The regex to match the static files.')
                            ->example('/\.(ttf|eot|otf|woff2)$/')
                        ->end()
                        ->integerNode('max_image_cache_entries')
                            ->defaultValue(60)
                            ->info('The maximum number of entries in the image cache.')
                            ->example([50, 100, 200])
                        ->end()
                        ->integerNode('max_image_age')
                            ->defaultValue(60 * 60 * 24 * 365)
                            ->info('The maximum number of seconds before the image cache is invalidated.')
                            ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                        ->end()
                        ->integerNode('max_font_cache_entries')
                            ->defaultValue(30)
                            ->info('The maximum number of entries in the font cache.')
                            ->example([30, 50, 100])
                        ->end()
                        ->integerNode('max_font_age')
                            ->defaultValue(60 * 60 * 24 * 365)
                            ->info('The maximum number of seconds before the font cache is invalidated.')
                            ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                        ->end()
                        ->integerNode('network_timeout_seconds')
                            ->defaultValue(3)
                            ->info('The network timeout in seconds before cache is called (for warm cache URLs only).')
                            ->example([1, 2, 5])
                        ->end()
                        ->arrayNode('warm_cache_urls')
                            ->treatNullLike([])
                            ->treatFalseLike([])
                            ->treatTrueLike([])
                            ->info('The URLs to warm the cache. The URLs shall be served by the application.')
                            ->arrayPrototype()
                            ->beforeNormalization()
                            ->ifString()
                                ->then(static fn (string $v): array => [
                                    'path' => $v,
                                ])
                            ->end()
                            ->children()
                                ->scalarNode('path')
                                    ->isRequired()
                                    ->info('The URL of the shortcut.')
                                    ->example('app_homepage')
                                ->end()
                                ->arrayNode('params')
                                    ->treatFalseLike([])
                                    ->treatTrueLike([])
                                    ->treatNullLike([])
                                    ->prototype('variable')->end()
                                    ->info('The parameters of the action.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('scope')
                ->cannotBeEmpty()
                ->defaultValue('/')
                ->info('The scope of the service worker.')
                ->example('/app/')
            ->end()
            ->booleanNode('use_cache')
                ->defaultTrue()
                ->info('Whether the service worker should use the cache.')
            ->end()
        ->end()
    ->end()
->end()
        ->end();
};
