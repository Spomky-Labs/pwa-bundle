<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
final class CommandTest extends KernelTestCase
{
    private static Application $application;

    protected function setUp(): void
    {
        self::cleanupFolder();
        self::$application = new Application(self::$kernel);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        self::cleanupFolder();
        parent::tearDown();
    }

    #[Test]
    public static function theCommandCanGenerateTheManifestAndIcons(): void
    {
        // Given
        $command = self::$application->find('pwa:build');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertStringContainsString('PWA Manifest Generator', $commandTester->getDisplay());
        static::assertFileExists(sprintf('%s/samples/manifest/my-pwa.json', self::$kernel->getCacheDir()));
        static::assertDirectoryExists(sprintf('%s/samples/icons', self::$kernel->getCacheDir()));
        static::assertDirectoryExists(sprintf('%s/samples/screenshots', self::$kernel->getCacheDir()));
        static::assertDirectoryExists(sprintf('%s/samples/shortcut_icons', self::$kernel->getCacheDir()));
    }

    #[Test]
    public static function theCommandCanCreateTheServiceWorker(): void
    {
        // Given
        $command = self::$application->find('pwa:sw');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertStringContainsString('Workbox Service Worker', $commandTester->getDisplay());
        static::assertFileExists(sprintf('%s/samples/sw/my-sw.js', self::$kernel->getCacheDir()));
    }

    private static function cleanupFolder(): void
    {
        $filesystem = self::getContainer()->get(Filesystem::class);
        $filesystem->remove(sprintf('%s/samples', self::$kernel->getCacheDir()));
    }
}
