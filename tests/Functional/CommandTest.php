<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class CommandTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        $filesystem = self::getContainer()->get('filesystem');
        $filesystem->remove(sprintf('%s/samples', self::$kernel->getCacheDir()));

        parent::tearDown();
    }

    #[Test]
    public static function theCommandCanGenerateTheManifestAndIcons(): void
    {
        // Given
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $filesystem = self::getContainer()->get('filesystem');
        $filesystem->remove(sprintf('%s/samples', $kernel->getCacheDir()));

        $command = $application->find('pwa:build');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        static::assertStringContainsString('PWA Manifest Generator', $output);
        static::assertFileExists(sprintf('%s/samples/manifest/my-pwa.json', $kernel->getCacheDir()));
        static::assertDirectoryExists(sprintf('%s/samples/icons', $kernel->getCacheDir()));
        static::assertDirectoryExists(sprintf('%s/samples/screenshots', $kernel->getCacheDir()));
        static::assertDirectoryExists(sprintf('%s/samples/shortcut_icons', $kernel->getCacheDir()));
    }

    #[Test]
    public static function theCommandCanCreateTheServiceWorker(): void
    {
        // Given
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $filesystem = self::getContainer()->get('filesystem');
        $filesystem->remove(sprintf('%s/samples', $kernel->getCacheDir()));

        $command = $application->find('pwa:sw');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        static::assertStringContainsString('Workbox Service Worker', $output);
        static::assertFileExists(sprintf('%s/samples/sw/my-sw.js', $kernel->getCacheDir()));
    }
}
