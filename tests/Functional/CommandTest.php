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
        static::assertDirectoryExists(sprintf('%s/samples/icons', self::$kernel->getCacheDir()));
        static::assertDirectoryExists(sprintf('%s/samples/screenshots', self::$kernel->getCacheDir()));
        foreach (self::expectedFiles() as $name => $file) {
            static::assertFileExists($file, sprintf('File "%s" does not exist.', $name));
        }
    }

    private static function cleanupFolder(): void
    {
        $filesystem = self::getContainer()->get(Filesystem::class);
        $filesystem->remove(sprintf('%s/samples', self::$kernel->getCacheDir()));
    }

    /**
     * @return iterable<string>
     */
    private static function expectedFiles(): iterable
    {
        yield 'sw' => sprintf('%s/samples/sw/my-sw.js', self::$kernel->getCacheDir());
        yield 'manifest' => sprintf('%s/samples/manifest/my-pwa.json', self::$kernel->getCacheDir());
        yield 'icon 48' => sprintf('%s/samples/icons/icon--48x48-1dc988f5.json', self::$kernel->getCacheDir());
        yield 'icon 72' => sprintf('%s/samples/icons/icon--72x72-5446402b.json', self::$kernel->getCacheDir());
        yield 'icon 96' => sprintf('%s/samples/icons/icon--96x96-d6d73d91.json', self::$kernel->getCacheDir());
        yield 'icon 128' => sprintf('%s/samples/icons/icon--128x128-d7e6af19.json', self::$kernel->getCacheDir());
        yield 'icon 256' => sprintf('%s/samples/icons/icon--256x256-0091eae5.json', self::$kernel->getCacheDir());
        yield 'icon any' => sprintf('%s/samples/icons/icon--any-2a9c5120.svg', self::$kernel->getCacheDir());
        yield 'icon 48 maskable' => sprintf(
            '%s/samples/icons/icon-maskable-48x48-bda4c927.json',
            self::$kernel->getCacheDir()
        );
        yield 'icon 72 maskable' => sprintf(
            '%s/samples/icons/icon-maskable-72x72-6019b5fd.json',
            self::$kernel->getCacheDir()
        );
        yield 'icon 96 maskable' => sprintf(
            '%s/samples/icons/icon-maskable-96x96-b4c4250c.json',
            self::$kernel->getCacheDir()
        );
        yield 'icon 128 maskable' => sprintf(
            '%s/samples/icons/icon-maskable-128x128-9be87901.json',
            self::$kernel->getCacheDir()
        );
        yield 'icon 256 maskable' => sprintf(
            '%s/samples/icons/icon-maskable-256x256-8f61caf3.json',
            self::$kernel->getCacheDir()
        );
        yield 'screenshot' => sprintf(
            '%s/samples/screenshots/screenshot--1024x1920-a8c03e1d.json',
            self::$kernel->getCacheDir()
        );
    }
}
