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
    #[Test]
    public static function theCommandCanGenerateTheManifestAndIcons(): void
    {
        // Given
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('pwa:build');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([
            'public_folder' => sprintf('%s/samples', $kernel->getCacheDir()),
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        static::assertStringContainsString('PWA Manifest Generator', $output);
    }
}
