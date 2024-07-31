<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use Ergebnis\PHPUnit\SlowTestDetector\Attribute\MaximumDuration;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use function assert;

/**
 * @internal
 */
final class TakeScreenshotCommandTest extends AbstractPwaTestCase
{
    #[Test]
    #[MaximumDuration(1500)]
    public static function aScreenshotIsCorrectlyTaken(): never
    {
        static::markTestSkipped('This test is skipped as it requires a running server.');
        // Given
        $command = self::$application->find('pwa:create:screenshot');
        $commandTester = new CommandTester($command);
        assert(self::$kernel !== null);
        $output = sprintf('%s/samples/screenshots/', self::$kernel->getCacheDir());

        // When
        $commandTester->execute([
            'url' => 'https://symfony.com',
            '--output' => $output,
            '--filename' => 'screenshot',
            '--width' => '1024',
            '--height' => '1920',
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists($output);
    }
}
