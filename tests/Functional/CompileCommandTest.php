<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class CompileCommandTest extends AbstractPwaTestCase
{
    #[Test]
    public static function aScreenshotIsCorrectlyTake(): void
    {
        // Given
        $command = self::$application->find('asset-map:compile');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/site.webmanifest');
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/sw.js');
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/360x800-86e5e530cd7674b4f1137a418b6d0264.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/390x844-3f5c4bdccd303b49c95aa4344651c7e2.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/600x400-a6d84c84616946feb5f92f8ca0ae4047.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/780x360-5bf5dc07ede9d26a9b2e9dc9f53d1959.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/screenshots/915x412-6141e808964c20e880f141190100d6e6.svg'
        );
        static::assertFileExists(
            self::$kernel->getCacheDir() . '/output/assets/pwa/1920x1920-862cb89ba358ac021badfbe32a89bbfb.svg'
        );
    }
}
