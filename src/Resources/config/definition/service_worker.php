<?php

declare(strict_types=1);

use SpomkyLabs\PwaBundle\CachingStrategy\CacheStrategyInterface;
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
                ->arrayNode('workbox')
                    ->info('The configuration of the workbox.')
                    ->canBeDisabled()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['asset_cache'])) {
                                return $v;
                            }
                            $v['asset_cache'] = array_filter([
                                'enabled' => true,
                                'cache_name' => $v['asset_cache_name'] ?? 'assets',
                                'regex' => $v['static_regex'] ?? '/\.(css|js|json|xml|txt|map|ico|png|jpe?g|gif|svg|webp|bmp)$/',
                            ], static fn (mixed $v): bool => $v !== null);

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['image_cache'])) {
                                return $v;
                            }
                            $v['image_cache'] = array_filter([
                                'enabled' => true,
                                'cache_name' => $v['image_cache_name'] ?? 'images',
                                'regex' => $v['image_regex'] ?? '/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/',
                                'max_entries' => $v['max_image_cache_entries'] ?? 60,
                                'max_age' => $v['max_image_age'] ?? 60 * 60 * 24 * 365,
                            ], static fn (mixed $v): bool => $v !== null);

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['font_cache'])) {
                                return $v;
                            }
                            $v['font_cache'] = array_filter([
                                'enabled' => true,
                                'cache_name' => $v['font_cache_name'] ?? 'fonts',
                                'regex' => $v['font_regex'] ?? '/\.(ttf|eot|otf|woff2)$/',
                                'max_entries' => $v['max_font_cache_entries'] ?? 60,
                                'max_age' => $v['max_font_age'] ?? 60 * 60 * 24 * 365,
                            ], static fn (mixed $v): bool => $v !== null);

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => true)
                        ->then(static function (mixed $v): array {
                            if (isset($v['resource_caches'])) {
                                return $v;
                            }
                            $v['resource_caches'][] = [
                                'match_callback' => 'navigate',
                                'preload_urls' => $v['warm_cache_urls'] ?? [],
                                'cache_name' => $v['page_cache_name'] ?? 'pages',
                                'network_timeout' => $v['network_timeout_seconds'] ?? 3,
                            ];

                            return $v;
                        })
                    ->end()
                    ->children()
                        ->booleanNode('use_cdn')
                            ->defaultFalse()
                            ->info('Whether to use the local workbox or the CDN.')
                        ->end()
                        ->arrayNode('google_fonts')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_prefix')
                                    ->defaultNull()
                                    ->info('The cache prefix for the Google fonts.')
                                ->end()
                                ->scalarNode('max_age')
                                    ->defaultNull()
                                    ->info('The maximum age of the Google fonts cache (in seconds).')
                                ->end()
                                ->integerNode('max_entries')
                                    ->defaultNull()
                                    ->info('The maximum number of entries in the Google fonts cache.')
                                ->end()
                            ->end()
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
                        ->arrayNode('offline_fallback')
                            ->treatNullLike([])
                            ->treatFalseLike([])
                            ->treatTrueLike([])
                            ->children()
                                ->append(getUrlNode('page', 'The URL of the offline page fallback.'))
                                ->append(getUrlNode('image', 'The URL of the offline image fallback.'))
                                ->append(getUrlNode('font', 'The URL of the offline font fallback.'))
                            ->end()
                        ->end()
                        ->arrayNode('image_cache')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_name')
                                    ->defaultValue('images')
                                    ->info('The name of the image cache.')
                                ->end()
                                ->scalarNode('regex')
                                    ->defaultValue('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                                    ->info('The regex to match the images.')
                                    ->example('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                                ->end()
                                ->integerNode('max_entries')
                                    ->defaultValue(60)
                                    ->info('The maximum number of entries in the image cache.')
                                    ->example([50, 100, 200])
                                ->end()
                                ->scalarNode('max_age')
                                    ->defaultValue(60 * 60 * 24 * 365)
                                    ->info('The maximum number of seconds before the image cache is invalidated.')
                                    ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('asset_cache')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_name')
                                    ->defaultValue('assets')
                                    ->info('The name of the asset cache.')
                                ->end()
                                ->scalarNode('regex')
                                    ->defaultValue('/\.(css|js|json|xml|txt|map|ico|png|jpe?g|gif|svg|webp|bmp)$/')
                                    ->info('The regex to match the assets.')
                                    ->example('/\.(css|js|json|xml|txt|map|ico|png|jpe?g|gif|svg|webp|bmp)$/')
                                ->end()
                                ->scalarNode('max_age')
                                    ->defaultValue(60 * 60 * 24 * 365)
                                    ->info('The maximum number of seconds before the asset cache is invalidated.')
                                    ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('font_cache')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('cache_name')
                                    ->defaultValue('fonts')
                                    ->info('The name of the font cache.')
                                ->end()
                                ->scalarNode('regex')
                                    ->defaultValue('/\.(ttf|eot|otf|woff2)$/')
                                    ->info('The regex to match the fonts.')
                                    ->example('/\.(ttf|eot|otf|woff2)$/')
                                ->end()
                                ->integerNode('max_entries')
                                    ->defaultValue(60)
                                    ->info('The maximum number of entries in the image cache.')
                                    ->example([50, 100, 200])
                                ->end()
                                ->integerNode('max_age')
                                    ->defaultValue(60 * 60 * 24 * 365)
                                    ->info('The maximum number of seconds before the font cache is invalidated.')
                                    ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('resource_caches')
                            ->treatNullLike([])
                            ->treatFalseLike([])
                            ->treatTrueLike([])
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('match_callback')
                                        ->isRequired()
                                        ->info('The regex or callback function to match the URLs.')
                                        ->example(['/^\/api\//', '({url}) url.pathname === "/api/"'])
                                    ->end()
                                    ->scalarNode('cache_name')
                                        ->info('The name of the page cache.')
                                        ->example(['pages', 'api'])
                                    ->end()
                                    ->integerNode('network_timeout')
                                        ->defaultValue(3)
                                        ->info(
                                            'The network timeout in seconds before cache is called (for "NetworkFirst" and "NetworkOnly" strategies).'
                                        )
                                        ->example([1, 2, 5])
                                    ->end()
                                    ->scalarNode('strategy')
                                        ->defaultValue('NetworkFirst')
                                        ->info(
                                            'The caching strategy. Only "NetworkFirst", "CacheFirst" and "StaleWhileRevalidate" are supported.'
                                        )
                                        ->example(['NetworkFirst', 'StaleWhileRevalidate', 'CacheFirst'])
                                        ->validate()
                                            ->ifNotInArray(CacheStrategyInterface::STRATEGIES)
                                            ->thenInvalid(
                                                'Invalid caching strategy "%s". Should be one of: ' . implode(
                                                    ', ',
                                                    CacheStrategyInterface::STRATEGIES
                                                )
                                            )
                                        ->end()
                                    ->end()
                                    ->scalarNode('max_entries')
                                        ->defaultNull()
                                        ->info(
                                            'The maximum number of entries in the cache (for "CacheFirst" and "NetworkFirst" strategy only).'
                                        )
                                    ->end()
                                    ->scalarNode('max_age')
                                        ->defaultNull()
                                        ->info(
                                            'The maximum number of seconds before the cache is invalidated (for "CacheFirst" and "NetWorkFirst" strategy only).'
                                        )
                                    ->end()
                                    ->booleanNode('broadcast')
                                        ->defaultFalse()
                                        ->info(
                                            'Whether to broadcast the cache update events (for "StaleWhileRevalidate" strategy only).'
                                        )
                                    ->end()
                                    ->booleanNode('range_requests')
                                        ->defaultFalse()
                                        ->info(
                                            'Whether to support range requests (for "CacheFirst" strategy only).'
                                        )
                                    ->end()
                                    ->arrayNode('cacheable_response_headers')
                                        ->treatNullLike([])
                                        ->treatFalseLike([])
                                        ->treatTrueLike([])
                                        ->scalarPrototype()->end()
                                        ->info(
                                            'The cacheable response headers. If set to ["X-Is-Cacheable" => "true"], only the response with the header "X-Is-Cacheable: true" will be cached.'
                                        )
                                    ->end()
                                    ->arrayNode('cacheable_response_statuses')
                                        ->treatNullLike([])
                                        ->treatFalseLike([])
                                        ->treatTrueLike([])
                                        ->integerPrototype()->end()
                                        ->info(
                                            'The cacheable response statuses. if set to [200], only 200 status will be cached.'
                                        )
                                    ->end()
                                    ->arrayNode('broadcast_headers')
                                        ->treatNullLike(['Content-Length', 'ETag', 'Last-Modified'])
                                        ->treatFalseLike(['Content-Length', 'ETag', 'Last-Modified'])
                                        ->treatTrueLike(['Content-Length', 'ETag', 'Last-Modified'])
                                        ->defaultValue(['Content-Length', 'ETag', 'Last-Modified'])
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->arrayNode('preload_urls')
                                        ->treatNullLike([])
                                        ->treatFalseLike([])
                                        ->treatTrueLike([])
                                        ->info(
                                            'The URLs to warm the cache. The URLs shall be served by the application.'
                                        )
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
                        ->end()
                        ->arrayNode('background_sync')
                            ->treatNullLike([])
                            ->treatFalseLike([])
                            ->treatTrueLike([])
                            ->info('The background sync configuration.')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('queue_name')
                                        ->isRequired()
                                        ->info('The name of the queue.')
                                        ->example(['api-requests', 'image-uploads'])
                                    ->end()
                                    ->scalarNode('match_callback')
                                        ->isRequired()
                                        ->info('The regex or callback function to match the URLs.')
                                        ->example(['/\/api\//'])
                                    ->end()
                                    ->scalarNode('method')
                                        ->defaultValue('POST')
                                        ->info('The HTTP method.')
                                        ->example(['POST', 'PUT', 'PATCH', 'DELETE'])
                                    ->end()
                                    ->scalarNode('broadcast_channel')
                                        ->defaultNull()
                                        ->info('The broadcast channel. Set null to disable.')
                                        ->example(['channel-1', 'background-sync-events'])
                                    ->end()
                                    ->integerNode('max_retention_time')
                                        ->defaultValue(60 * 24)
                                        ->info('The maximum retention time in minutes.')
                                    ->end()
                                    ->booleanNode('force_sync_fallback')
                                        ->defaultFalse()
                                        ->info(
                                            'If `true`, instead of attempting to use background sync events, always attempt to replay queued request at service worker startup. Most folks will not need this, unless you explicitly target a runtime like Electron that exposes the interfaces for background sync, but does not have a working implementation.'
                                        )
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('image_cache_name')
                            ->defaultValue('images')
                            ->info('The name of the image cache.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.image_cache.cache_name" instead.'
                            )
                        ->end()
                        ->scalarNode('font_cache_name')
                            ->defaultValue('fonts')
                            ->info('The name of the font cache.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.font_cache.cache_name" instead.'
                            )
                        ->end()
                        ->scalarNode('page_cache_name')
                            ->defaultValue('pages')
                            ->info('The name of the page cache.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.resource_caches[].cache_name" instead.'
                            )
                        ->end()
                        ->scalarNode('asset_cache_name')
                            ->defaultValue('assets')
                            ->info('The name of the asset cache.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.asset_cache.cache_name" instead.'
                            )
                        ->end()
                        ->append(getUrlNode('page_fallback', 'The URL of the offline page fallback.'))
                        ->append(getUrlNode('image_fallback', 'The URL of the offline image fallback.'))
                        ->append(getUrlNode('font_fallback', 'The URL of the offline font fallback.'))
                        ->scalarNode('image_regex')
                            ->defaultValue('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                            ->info('The regex to match the images.')
                            ->example('/\.(ico|png|jpe?g|gif|svg|webp|bmp)$/')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.image_cache.regex" instead.'
                            )
                        ->end()
                        ->scalarNode('static_regex')
                            ->defaultValue('/\.(css|js|json|xml|txt|map)$/')
                            ->info('The regex to match the static files.')
                            ->example('/\.(css|js|json|xml|txt|map)$/')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.asset_cache.regex" instead.'
                            )
                        ->end()
                        ->scalarNode('font_regex')
                            ->defaultValue('/\.(ttf|eot|otf|woff2)$/')
                            ->info('The regex to match the static files.')
                            ->example('/\.(ttf|eot|otf|woff2)$/')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.font_cache.regex" instead.'
                            )
                        ->end()
                        ->integerNode('max_image_cache_entries')
                            ->defaultValue(60)
                            ->info('The maximum number of entries in the image cache.')
                            ->example([50, 100, 200])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.image_cache.max_entries" instead.'
                            )
                        ->end()
                        ->integerNode('max_image_age')
                            ->defaultValue(60 * 60 * 24 * 365)
                            ->info('The maximum number of seconds before the image cache is invalidated.')
                            ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.image_cache.max_age" instead.'
                            )
                        ->end()
                        ->integerNode('max_font_cache_entries')
                            ->defaultValue(30)
                            ->info('The maximum number of entries in the font cache.')
                            ->example([30, 50, 100])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.font_cache.max_entries" instead.'
                            )
                        ->end()
                        ->integerNode('max_font_age')
                            ->defaultValue(60 * 60 * 24 * 365)
                            ->info('The maximum number of seconds before the font cache is invalidated.')
                            ->example([60 * 60 * 24 * 365, 60 * 60 * 24 * 30, 60 * 60 * 24 * 7])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.font_cache.max_age" instead.'
                            )
                        ->end()
                        ->integerNode('network_timeout_seconds')
                            ->defaultValue(3)
                            ->info('The network timeout in seconds before cache is called (for warm cache URLs only).')
                            ->example([1, 2, 5])
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.resource_caches[].network_timeout" instead.'
                            )
                        ->end()
                        ->arrayNode('warm_cache_urls')
                            ->treatNullLike([])
                            ->treatFalseLike([])
                            ->treatTrueLike([])
                            ->info('The URLs to warm the cache. The URLs shall be served by the application.')
                            ->setDeprecated(
                                'spomky-labs/phpwa',
                                '1.1.0',
                                'The "%node%" option is deprecated and will be removed in 2.0.0. Please use "pwa.serviceworker.workbox.resource_caches[].urls" instead.'
                            )
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
        ->end()
    ->end()
->end()
        ->end();
};
