<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use Ergebnis\PHPUnit\SlowTestDetector\Attribute\MaximumDuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use function assert;

/**
 * @internal
 */
final class CompileCommandTest extends AbstractPwaTestCase
{
    #[Test]
    #[DataProvider('provideCommands')]
    #[MaximumDuration(2000)]
    public static function theFileAreCompiled(string $command): void
    {
        // Given
        $command = self::$application->find($command);
        $commandTester = new CommandTester($command);
        assert(self::$kernel !== null);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/site.webmanifest');
        static::assertFileExists(self::$kernel->getCacheDir() . '/output/sw.js');
    }

    /**
     * @return iterable<string[]>
     */
    public static function provideCommands(): iterable
    {
        yield ['pwa:compile'];
        yield ['asset-map:compile'];
    }
}
