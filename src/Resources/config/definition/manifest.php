<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

require_once __DIR__ . '/utils/file_handlers.php';
require_once __DIR__ . '/utils/icons.php';
require_once __DIR__ . '/utils/launch_handler.php';
require_once __DIR__ . '/utils/protocol_handlers.php';
require_once __DIR__ . '/utils/related_applications.php';
require_once __DIR__ . '/utils/screenshots.php';
require_once __DIR__ . '/utils/shared_target.php';
require_once __DIR__ . '/utils/shortcuts.php';
require_once __DIR__ . '/utils/url_node.php';
require_once __DIR__ . '/utils/widgets.php';

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->arrayNode('manifest')
                ->canBeEnabled()
                ->children()
                    ->scalarNode('public_url')
                        ->defaultValue('/site.webmanifest')
                        ->cannotBeEmpty()
                        ->info('The public URL of the manifest file.')
                        ->example('/site.manifest')
                    ->end()
                    ->booleanNode('use_credentials')
                        ->defaultTrue()
                        ->info('Indicates whether the manifest should be fetched with credentials.')
                    ->end()
                    ->scalarNode('background_color')
                        ->info(
                            'The background color of the application. It  should match the background-color CSS property in the sites stylesheet for a smooth transition between launching the web application and loading the site\'s content.'
                        )
                        ->example('red')
                    ->end()
                    ->arrayNode('categories')
                        ->info('The categories of the application.')
                        ->example([['news', 'sports', 'lifestyle']])
                        ->scalarPrototype()
                    ->end()
                ->end()
                ->scalarNode('description')
                    ->info('The description of the application.')
                    ->example('My awesome application')
                ->end()
                ->scalarNode('display')
                    ->info('The display mode of the application.')
                    ->example('standalone')
                ->end()
                ->arrayNode('display_override')
                    ->info(
                        'A sequence of display modes that the browser will consider before using the display member.'
                    )
                    ->example([['fullscreen', 'minimal-ui']])
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('id')
                    ->info('A string that represents the identity of the web application.')
                    ->example('?homescreen=1')
                ->end()
                ->scalarNode('orientation')
                    ->info('The orientation of the application.')
                    ->example('portrait-primary')
                ->end()
                ->scalarNode('dir')
                    ->info('The direction of the application.')
                    ->example('rtl')
                ->end()
                ->scalarNode('lang')
                    ->info('The language of the application.')
                    ->example('ar')
                ->end()
                ->scalarNode('name')
                    ->info('The name of the application.')
                    ->example('My awesome application')
                ->end()
                ->scalarNode('short_name')
                    ->info('The short name of the application.')
                    ->example('awesome_app')
                ->end()
                ->scalarNode('scope')
                    ->info('The scope of the application.')
                    ->example('/app/')
                ->end()
                ->scalarNode('start_url')
                    ->info('The start URL of the application.')
                    ->example('https://example.com')
                ->end()
                ->scalarNode('theme_color')
                    ->info('The theme color of the application.')
                    ->example('red')
                ->end()
                ->arrayNode('edge_side_panel')
                    ->info('Specifies whether or not your app supports the side panel view in Microsoft Edge.')
                    ->children()
                        ->integerNode('preferred_width')
                            ->info('Specifies the preferred width of the side panel view in Microsoft Edge.')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('iarc_rating_id')
                    ->info(
                        'Specifies the International Age Rating Coalition (IARC) rating ID for the app. See https://www.globalratings.com/how-iarc-works.aspx for more information.'
                    )
                ->end()
                ->arrayNode('scope_extensions')
                    ->info(
                        'Specifies a list of origin patterns to associate with. This allows for your app to control multiple subdomains and top-level domains as a single entity.'
                    )
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('origin')
                                ->isRequired()
                                ->info('Specifies the origin pattern to associate with.')
                                ->example('*.foo.com')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('handle_links')
                    ->info('Specifies the default link handling for the web app.')
                    ->example(['auto', 'preferred', 'not-preferred'])
                ->end()
                ->append(getIconsNode('The icons of the application.'))
                ->append(getScreenshotsNode('The screenshots of the application.'))
                ->append(getFileHandlersNode())
                ->append(getLaunchHandlerNode())
                ->append(getProtocolHandlersNode())
                ->booleanNode('prefer_related_applications')
                    ->defaultValue(false)
                    ->info('prefer related native applications (instead of this application)')
                ->end()
                ->append(setupRelatedApplications())
                ->append(setupShortcuts())
                ->append(setupSharedTarget())
                ->append(setupWidgets())
            ->end()
        ->end()
    ->end();
};
