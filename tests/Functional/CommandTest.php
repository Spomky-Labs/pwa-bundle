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
        $commandTester->execute([
            '--url_prefix' => '/foo/bar',
            '--public_folder' => sprintf('%s/samples', $kernel->getCacheDir()),
            '--asset_folder' => '/data',
            '--output' => 'my-pwa.json',
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        static::assertStringContainsString('PWA Manifest Generator', $output);
        static::assertFileExists(sprintf('%s/samples/my-pwa.json', $kernel->getCacheDir()));
        static::assertDirectoryExists(sprintf('%s/samples/data', $kernel->getCacheDir()));
    }
}
