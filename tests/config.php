<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SpomkyLabs\PwaBundle\Tests\DummyImageProcessor;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(DummyImageProcessor::class);

    $container->services()
        ->load('SpomkyLabs\\PwaBundle\\Tests\\Controller\\', __DIR__ . '/Controller/')
        ->tag('controller.service_arguments')
    ;

    $container->extension('framework', [
        'test' => true,
        'secret' => 'test',
        'http_method_override' => true,
        'session' => [
            'storage_factory_id' => 'session.storage.factory.mock_file',
        ],
        'asset_mapper' => [
            'paths' => [
                'tests/images' => 'pwa',
            ],
        ],
        'router' => [
            'utf8' => true,
            'resource' => '%kernel.project_dir%/tests/routes.php',
        ],
    ]);
    $container->extension('pwa', [
        'image_processor' => DummyImageProcessor::class,
        'background_color' => 'red',
        'categories' => ['books', 'education', 'medical'],
        'description' => 'Awesome application that will help you achieve your dreams.',
        'display' => 'standalone',
        'display_override' => ['fullscreen', 'minimal-ui'],
        'file_handlers' => [
            [
                'action' => 'audio_file_handler',
                'action_params' => [
                    'param1' => 'audio',
                ],
                'accept' => [
                    'audio/wav' => ['.wav'],
                    'audio/x-wav' => ['.wav'],
                    'audio/mpeg' => ['.mp3'],
                    'audio/mp4' => ['.mp4'],
                    'audio/aac' => ['.adts'],
                    'audio/ogg' => ['.ogg'],
                    'application/ogg' => ['.ogg'],
                    'audio/webm' => ['.webm'],
                    'audio/flac' => ['.flac'],
                    'audio/mid' => ['.rmi', '.mid'],
                ],
            ],
        ],
        'icons' => [
            [
                'src' => 'pwa/1920x1920.svg',
                'sizes' => [48, 72, 96, 128, 256],
                'format' => 'webp',
            ],
            [
                'src' => 'pwa/1920x1920.svg',
                'sizes' => [48, 72, 96, 128, 256],
                'format' => 'png',
                'purpose' => 'maskable',
            ],
            [
                'src' => 'pwa/1920x1920.svg',
                'sizes' => [0],
            ],
        ],
        'id' => '?homescreen=1',
        'launch_handler' => [
            'client_mode' => ['focus-existing', 'auto'],
        ],
        'orientation' => 'portrait-primary',
        'prefer_related_applications' => true,
        'dir' => 'rtl',
        'lang' => 'ar',
        'name' => 'تطبيق رائع',
        'short_name' => 'رائع',
        'protocol_handlers' => [
            [
                'protocol' => 'web+jngl',
                'url' => '/lookup?type=%s',
            ],
            [
                'protocol' => 'web+jnglstore',
                'url' => '/shop?for=%s',
            ],
        ],
        'related_applications' => [
            [
                'platform' => 'play',
                'url' => 'https://play.google.com/store/apps/details?id=com.example.app1',
                'id' => 'com.example.app1',
            ],
            [
                'platform' => 'itunes',
                'url' => 'https://itunes.apple.com/app/example-app1/id123456789',
            ],
            [
                'platform' => 'windows',
                'url' => 'https://apps.microsoft.com/store/detail/example-app1/id123456789',
            ],
        ],
        'scope' => '/app/',
        'start_url' => 'https://example.com',
        'theme_color' => 'red',
        'screenshots' => [
            [
                'src' => 'pwa/screenshots/360x800.svg',
                'platform' => 'android',
                'format' => 'png',
                'label' => '360x800',
                'width' => 360,
                'height' => 800,
            ],
        ],
        'share_target' => [
            'action' => 'shared_content_receiver',
            'action_params' => [
                'param1' => 'value1',
                'param2' => 'value2',
            ],
            'method' => 'GET',
            'params' => [
                'title' => 'name',
                'text' => 'description',
                'url' => 'link',
            ],
        ],
        'shortcuts' => [
            [
                'name' => "Today's agenda",
                'url' => 'agenda',
                'url_params' => [
                    'date' => 'today',
                ],
                'description' => 'List of events planned for today',
            ],
            [
                'name' => 'New event',
                'url' => '/create/event',
            ],
            [
                'name' => 'New reminder',
                'url' => '/create/reminder',
                'icons' => [
                    [
                        'src' => 'pwa/1920x1920.svg',
                        'sizes' => [48, 72, 96, 128, 256],
                        'format' => 'webp',
                    ],
                    [
                        'src' => 'pwa/1920x1920.svg',
                        'sizes' => [48, 72, 96, 128, 256],
                        'format' => 'png',
                        'purpose' => 'maskable',
                    ],
                    [
                        'src' => 'pwa/1920x1920.svg',
                        'sizes' => [0],
                    ],
                ],
            ],
        ],
        'edge_side_panel' => [
            'preferred_width' => 480,
        ],
        'iarc_rating_id' => '123456',
        'scope_extensions' => [
            [
                'origin' => '*.foo.com',
            ],
            [
                'origin' => 'https://*.bar.com',
            ],
            [
                'origin' => 'https://*.baz.com',
            ],
        ],
        'widgets' => [
            [
                'name' => 'PWAmp mini player',
                'description' => 'widget to control the PWAmp music player',
                'tag' => 'pwamp',
                'template' => 'pwamp-template',
                'ms_ac_template' => 'widgets/mini-player-template.json',
                'data' => 'widgets/mini-player-data.json',
                'type' => 'application/json',
                'screenshots' => [
                    [
                        'src' => 'pwa/1920x1920.svg',
                        'label' => 'The PWAmp mini-player widget',
                    ],
                ],
                'icons' => [
                    [
                        'src' => 'pwa/1920x1920.svg',
                        'sizes' => [16, 48],
                        'format' => 'webp',
                    ],
                ],
                'auth' => false,
                'update' => 86400,
            ],
        ],
        'handle_links' => 'auto',
        'serviceworker' => [
            'src' => '/sw/my-sw.js',
            'scope' => '/',
            'use_cache' => true,
        ],
    ]);
};
