<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use Ergebnis\PHPUnit\SlowTestDetector\Attribute\MaximumDuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class CompileCommandTest extends AbstractPwaTestCase
{
    #[Test]
    #[DataProvider('provideCommands')]
    #[MaximumDuration(2000)]
    public static function theFileAreCompiled(string $commandName): void
    {
        // Given
        $command = self::$application->find($commandName);
        $commandTester = new CommandTester($command);
        static::assertInstanceOf(KernelInterface::class, self::$kernel);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/site.webmanifest');
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/sw.js');
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/favicon.ico');
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/idb/index.cjs');
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/workbox/workbox-sw.js');
    }

    /**
     * @return iterable<string[]>
     */
    public static function provideCommands(): iterable
    {
        yield ['pwa:compile'];
        yield ['asset-map:compile'];
    }

    #[Test]
    public static function onlyTheManifestAndTheServiceWorkerAreCompiled(): void
    {
        // Given
        $command = self::$application->find('pwa:compile');
        $commandTester = new CommandTester($command);
        static::assertInstanceOf(KernelInterface::class, self::$kernel);

        // When
        $commandTester->execute([
            '--context-only' => true,
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/site.webmanifest');
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/sw.js');
        static::assertFileDoesNotExist(self::$kernel->getCacheDir() . '/output/favicon.ico');
        static::assertFileDoesNotExist(self::$kernel->getCacheDir() . '/output/idb/index.cjs');
        static::assertFileDoesNotExist(self::$kernel->getCacheDir() . '/output/workbox/workbox-sw.js');
    }
}
