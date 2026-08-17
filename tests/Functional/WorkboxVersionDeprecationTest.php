<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use const E_USER_DEPRECATED;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\SpomkyLabsPwaBundle;
use function str_contains;
use Symfony\Component\Config\Definition\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * The Workbox version and the CDN are deprecated: everything the bundle generates is written against the
 * version it ships, so pinning another one only ever produces a service worker that breaks in the browser.
 *
 * What is tested here as much as the deprecation itself is its absence: the section used to be filled in
 * with its own defaults during normalization, which would have made the bundle deprecate options no
 * application ever configured.
 */
final class WorkboxVersionDeprecationTest extends TestCase
{
    #[Test]
    public function anApplicationConfiguringNoWorkboxVersionIsNotDeprecated(): void
    {
        $deprecations = $this->process([
            'serviceworker' => [
                'enabled' => true,
                'src' => 'sw.js',
            ],
        ]);

        static::assertSame([], $deprecations);
    }

    #[Test]
    public function theRemainingWorkboxOptionsAreNotDeprecated(): void
    {
        $deprecations = $this->process([
            'serviceworker' => [
                'enabled' => true,
                'src' => 'sw.js',
                'workbox' => [
                    'config' => [
                        'debug' => false,
                        'workbox_public_url' => '/assets/workbox',
                    ],
                ],
            ],
        ]);

        static::assertSame([], $deprecations);
    }

    #[Test]
    public function pinningTheVersionIsDeprecated(): void
    {
        $deprecations = $this->process([
            'serviceworker' => [
                'enabled' => true,
                'src' => 'sw.js',
                'workbox' => [
                    'config' => [
                        'version' => '7.3.0',
                    ],
                ],
            ],
        ]);

        static::assertCount(1, $deprecations);
        static::assertStringContainsString('pwa.serviceworker.workbox.config.version', $deprecations[0]);
        static::assertStringContainsString('will be removed in 2.0.0', $deprecations[0]);
    }

    #[Test]
    public function usingTheCdnIsDeprecated(): void
    {
        $deprecations = $this->process([
            'serviceworker' => [
                'enabled' => true,
                'src' => 'sw.js',
                'workbox' => [
                    'config' => [
                        'use_cdn' => true,
                    ],
                ],
            ],
        ]);

        static::assertCount(1, $deprecations);
        static::assertStringContainsString('pwa.serviceworker.workbox.config.use_cdn', $deprecations[0]);
    }

    /**
     * The options predating the "config" section still move into it, so that an application that never
     * migrated keeps the service worker it had.
     *
     * Such an application is told twice, once for the spelling it used and once for the one the value was
     * migrated into. Both are true, and both ask for the same thing, so the duplicate is left alone rather
     * than silenced by dropping a deprecation that has been reported since 1.5.0.
     */
    #[Test]
    public function theLegacyOptionsStillReachTheConfigSection(): void
    {
        $config = [];
        $deprecations = $this->process([
            'serviceworker' => [
                'enabled' => true,
                'src' => 'sw.js',
                'workbox' => [
                    'use_cdn' => true,
                    'version' => '7.3.0',
                ],
            ],
        ], $config);

        static::assertTrue($config['serviceworker']['workbox']['config']['use_cdn']);
        static::assertSame('7.3.0', $config['serviceworker']['workbox']['config']['version']);

        $paths = array_map(
            static fn (string $message): string => preg_replace('/^.*The "([^"]+)".*$/s', '$1', $message),
            $deprecations
        );
        static::assertContains('pwa.serviceworker.workbox.use_cdn', $paths);
        static::assertContains('pwa.serviceworker.workbox.version', $paths);
    }

    /**
     * An application declaring nothing gets the defaults of the DTO rather than a "config" section written
     * for it, which is what keeps the deprecations silent.
     */
    #[Test]
    public function theConfigSectionIsNotFilledInForAnApplicationThatDeclaredNone(): void
    {
        $config = [];
        $this->process([
            'serviceworker' => [
                'enabled' => true,
                'src' => 'sw.js',
            ],
        ], $config);

        static::assertArrayNotHasKey('config', $config['serviceworker']['workbox']);
    }

    /**
     * @param array<string, mixed>       $configuration
     * @param array<string, mixed>|null  $processed
     *
     * @return list<string>
     */
    private function process(array $configuration, null|array &$processed = null): array
    {
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $processed = (new Processor())->processConfiguration(
                new Configuration(new SpomkyLabsPwaBundle(), null, 'pwa'),
                [$configuration]
            );
        } finally {
            restore_error_handler();
        }

        return array_values(array_filter(
            $deprecations,
            static fn (string $message): bool => str_contains($message, 'Workbox') || str_contains($message, 'workbox')
        ));
    }
}
