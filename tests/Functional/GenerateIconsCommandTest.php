<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class GenerateIconsCommandTest extends AbstractPwaTestCase
{
    #[Test]
    public static function aScreenshotIsCorrectlyTake(): void
    {
        // Given
        $command = self::$application->find('pwa:generate-icons');
        $commandTester = new CommandTester($command);
        $output = sprintf('%s/samples/icons', self::$kernel->getCacheDir());

        // When
        $commandTester->execute([
            'source' => __DIR__ . '/../images/1920x1920.svg',
            'output' => $output,
            '--format' => 'png',
            'sizes' => [192, 512],
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists(sprintf('%s/icon-192x192.png', $output));
        static::assertFileExists(sprintf('%s/icon-512x512.png', $output));
    }
}
