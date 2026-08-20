<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Dto;

use const PHP_BINARY;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\Screenshot;
use SpomkyLabs\PwaBundle\Dto\Shortcut;
use function sprintf;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @internal
 */
final class TranslatableTraitTest extends TestCase
{
    #[Test]
    public function theConfiguredTextIsWrappedWhenTheTranslationComponentIsInstalled(): void
    {
        $manifest = new Manifest();
        $manifest->name = 'pwa.name';
        $manifest->categories = ['pwa.categories.0'];

        $name = $manifest->getName();
        static::assertInstanceOf(TranslatableMessage::class, $name);
        static::assertSame('pwa.name', $name->getMessage());
        static::assertSame('pwa', $name->getDomain());

        $categories = $manifest->getCategories();
        static::assertContainsOnlyInstancesOf(TranslatableMessage::class, $categories);
    }

    #[Test]
    public function nothingIsWrappedWhenThereIsNothingToTranslate(): void
    {
        $manifest = new Manifest();

        static::assertNull($manifest->getName());
        static::assertNull($manifest->getShortName());
        static::assertNull($manifest->getDescription());
        static::assertSame([], $manifest->getCategories());
        static::assertNull((new Screenshot())->getLabel());
        static::assertNull((new Shortcut())->getDescription());
    }

    /**
     * symfony/translation-contracts is commonly installed on its own (twig-bridge, security-core, …), so the
     * interface is there while TranslatableMessage is not. The bundle used to look at the interface only and
     * blew up on the missing class, taking the whole manifest down with it.
     */
    #[Test]
    public function theRawTextIsProvidedWhenTheTranslationComponentIsMissing(): void
    {
        $probe = __DIR__ . '/without_translation_component.php';
        $command = sprintf('%s %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($probe));

        $output = shell_exec($command);
        static::assertIsString($output, 'the probe process could not be started');

        $result = json_decode($output, true);
        static::assertIsArray(
            $result,
            sprintf('the probe did not report anything usable, it printed: %s', $output)
        );

        if (isset($result['skipped'])) {
            static::markTestSkipped($result['skipped']);
        }

        static::assertTrue($result['contracts_available'], 'the probe did not reproduce the reported setup');
        static::assertSame('My Application', $result['name']);
        static::assertSame('App', $result['short_name']);
        static::assertSame('A very nice application', $result['description']);
        static::assertSame(['books', 'education'], $result['categories']);
        static::assertNull($result['untouched_description']);
        static::assertSame("Today's agenda", $result['shortcut_name']);
        static::assertSame('Events planned for today', $result['shortcut_description']);
        static::assertSame('The home page', $result['screenshot_label']);
    }
}
