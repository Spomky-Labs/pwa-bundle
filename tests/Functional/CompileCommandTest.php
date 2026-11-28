<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use Ergebnis\PHPUnit\SlowTestDetector\Attribute\MaximumDuration;
use const JSON_THROW_ON_ERROR;
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
    public static function theManifestCarriesNoConfigurationFlag(): void
    {
        // Given
        $command = self::$application->find('pwa:compile');
        $commandTester = new CommandTester($command);
        static::assertInstanceOf(KernelInterface::class, self::$kernel);

        // When
        $commandTester->execute([]);
        $manifest = json_decode(
            (string) file_get_contents(self::$kernel->getCacheDir() . '/output/site.webmanifest'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertIsArray($manifest);
        static::assertArrayNotHasKey('enabled', $manifest);
        static::assertArrayNotHasKey('use_credentials', $manifest);
    }

    #[Test]
    public static function theNoteTakingUrlCarriesTheMemberNameOfTheSpecification(): void
    {
        // Given
        $command = self::$application->find('pwa:compile');
        $commandTester = new CommandTester($command);
        static::assertInstanceOf(KernelInterface::class, self::$kernel);

        // When
        $commandTester->execute([]);
        $manifest = json_decode(
            (string) file_get_contents(self::$kernel->getCacheDir() . '/output/site.webmanifest'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertIsArray($manifest);
        static::assertSame([
            'new_note_url' => '/notes',
        ], $manifest['note_taking'] ?? null);
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
