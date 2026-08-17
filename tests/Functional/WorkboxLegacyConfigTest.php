<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use const E_USER_DEPRECATED;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\WorkboxConfig;
use SpomkyLabs\PwaBundle\SpomkyLabsPwaBundle;
use Symfony\Component\Config\Definition\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * The Workbox options predating the "config" section, and what an application configuring them ends up
 * with.
 *
 * Normalization stopped writing the whole section with its own defaults, so that deprecating
 * "config.use_cdn" and "config.version" would not report options nobody configured. What replaces the
 * written defaults are the ones "WorkboxConfig" carries, and these tests are what holds the two together:
 * the effective value is asserted for every shape a configuration can take, not the shape of the
 * normalized array.
 */
final class WorkboxLegacyConfigTest extends TestCase
{
    /**
     * @param array<string, mixed> $workbox
     * @param array{use_cdn: bool, version: string, workbox_public_url: string, debug: bool} $expected
     */
    #[Test]
    #[DataProvider('dataLegacyShapes')]
    public function everyConfigurationShapeResolvesToTheSameValuesAsBefore(array $workbox, array $expected): void
    {
        static::assertSame($expected, $this->resolve($workbox));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function dataLegacyShapes(): iterable
    {
        $defaults = [
            'use_cdn' => false,
            'version' => '7.4.1',
            'workbox_public_url' => '/workbox',
            'debug' => true,
        ];

        yield 'Nothing configured' => [[], $defaults];

        yield 'Legacy use_cdn' => [[
            'use_cdn' => true,
        ], [
            ...$defaults,
            'use_cdn' => true,
        ]];

        yield 'Legacy version' => [[
            'version' => '7.3.0',
        ], [
            ...$defaults,
            'version' => '7.3.0',
        ]];

        yield 'Legacy workbox_public_url' => [[
            'workbox_public_url' => '/assets/workbox',
        ], [
            ...$defaults,
            'workbox_public_url' => '/assets/workbox',
        ]];

        yield 'Every legacy option at once' => [[
            'use_cdn' => true,
            'version' => '7.3.0',
            'workbox_public_url' => '/assets/workbox',
        ], [
            'use_cdn' => true,
            'version' => '7.3.0',
            'workbox_public_url' => '/assets/workbox',
            'debug' => true,
        ]];

        yield 'Legacy option left at its default' => [[
            'use_cdn' => false,
        ], $defaults];

        yield 'Current config section' => [[
            'config' => [
                'use_cdn' => true,
                'version' => '7.3.0',
            ],
        ], [
            ...$defaults,
            'use_cdn' => true,
            'version' => '7.3.0',
        ]];

        yield 'Partial config section' => [[
            'config' => [
                'debug' => false,
            ],
        ], [
            ...$defaults,
            'debug' => false,
        ]];
    }

    /**
     * The two spellings are not merged: a declared "config" section is taken as it stands, and the legacy
     * options next to it are ignored. Undocumented, but longstanding, so it is pinned rather than changed
     * under the cover of a deprecation.
     */
    #[Test]
    public function aDeclaredConfigSectionIgnoresTheLegacyOptionsBesideIt(): void
    {
        $resolved = $this->resolve([
            'use_cdn' => true,
            'version' => '7.3.0',
            'config' => [
                'workbox_public_url' => '/assets/workbox',
            ],
        ]);

        static::assertFalse($resolved['use_cdn']);
        static::assertSame('7.4.1', $resolved['version']);
        static::assertSame('/assets/workbox', $resolved['workbox_public_url']);
    }

    /**
     * What normalization stopped writing has to be exactly what the DTO falls back on, or an application
     * configuring nothing would silently change service worker.
     */
    #[Test]
    public function theDtoDefaultsAreTheOnesNormalizationUsedToWrite(): void
    {
        $config = new WorkboxConfig();

        static::assertFalse($config->useCDN);
        static::assertSame('7.4.1', $config->version);
        static::assertSame('/workbox', $config->workboxPublicUrl);
        static::assertTrue($config->debug);
    }

    /**
     * The effective Workbox settings of an application, the way the DTO resolves them: whatever the
     * normalized configuration carries, over the defaults for what it leaves out.
     *
     * @param array<string, mixed> $workbox
     *
     * @return array{use_cdn: bool, version: string, workbox_public_url: string, debug: bool}
     */
    private function resolve(array $workbox): array
    {
        $configuration = [
            'serviceworker' => [
                'enabled' => true,
                'src' => 'sw.js',
            ],
        ];
        if ($workbox !== []) {
            $configuration['serviceworker']['workbox'] = $workbox;
        }

        // The deprecations are the subject of another test case; here they are only in the way.
        set_error_handler(static fn (): bool => true, E_USER_DEPRECATED);
        try {
            $processed = (new Processor())->processConfiguration(
                new Configuration(new SpomkyLabsPwaBundle(), null, 'pwa'),
                [$configuration]
            );
        } finally {
            restore_error_handler();
        }

        /** @var array<string, mixed> $section */
        $section = $processed['serviceworker']['workbox']['config'] ?? [];
        $defaults = new WorkboxConfig();

        return [
            'use_cdn' => (bool) ($section['use_cdn'] ?? $defaults->useCDN),
            'version' => (string) ($section['version'] ?? $defaults->version),
            'workbox_public_url' => (string) ($section['workbox_public_url'] ?? $defaults->workboxPublicUrl),
            'debug' => (bool) ($section['debug'] ?? $defaults->debug),
        ];
    }
}
