<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use function assert;

/**
 * @internal
 */
final class CompileCommandTest extends AbstractPwaTestCase
{
    #[Test]
    public static function theFileAreCompiled(): void
    {
        // Given
        $command = self::$application->find('asset-map:compile');
        $commandTester = new CommandTester($command);
        assert(self::$kernel !== null);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/site.webmanifest');
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/sw.js');
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/360x800-huXlMM1.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/390x844-P1xL3M0.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/600x400-pthMhGF.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/780x360-W_XcB-3.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/915x412-YUHoCJZ.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/1920x1920-hiy4m6N.svg'
        );
    }
}
