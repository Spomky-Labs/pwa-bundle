<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class GenerateIconsFromConfigurationCommandTest extends AbstractPwaTestCase
{
    #[Test]
    public static function iconsAreCorrectlyCreated(): void
    {
        // Given
        $output = sprintf('%s/samples/icons', self::$kernel->getCacheDir());
        $command = self::$application->find('pwa:create:icons-from-config');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([
            'output' => $output,
        ]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertStringContainsString(
            'Icons have been generated. You can now use them in your application',
            $commandTester->getDisplay()
        );
    }
}
