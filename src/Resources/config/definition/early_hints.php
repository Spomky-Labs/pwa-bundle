<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
        ->arrayNode('early_hints')
        ->info('Early Hints (HTTP 103) configuration. Requires a compatible server (FrankenPHP, Caddy).')
        ->canBeEnabled()
        ->children()
        ->booleanNode('preload_manifest')
        ->defaultTrue()
        ->info('Preload the PWA manifest file.')
        ->end()
        ->booleanNode('preload_serviceworker')
        ->defaultFalse()
        ->info('Preload the service worker script. Disabled by default as SW registration is usually deferred.')
        ->end()
        ->booleanNode('preconnect_workbox_cdn')
        ->defaultTrue()
        ->info('Preconnect to Workbox CDN when using CDN mode.')
        ->end()
        ->end()
        ->end()
        ->end()
        ->end();
};
