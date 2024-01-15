<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class ServiceWorkerCommandTest extends AbstractPwaTestCase
{
    #[Test]
    public static function theCommandCanGenerateTheServiceWorker(): void
    {
        // Given
        $command = self::$application->find('pwa:sw');
        $commandTester = new CommandTester($command);
        $output = sprintf('%s/samples/my-sw.js', self::$kernel->getCacheDir());

        // When
        $commandTester->execute([
            'output' => $output,
            '--force' => true,
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();

        static::assertStringContainsString('PWA Service Worker Generator', $commandTester->getDisplay());
        static::assertFileExists($output);
    }
}
