<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use function array_diff;
use function array_unique;
use function array_values;
use PHPUnit\Framework\Attributes\Test;
use function preg_match_all;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\WindowsWidgets;

/**
 * @internal
 */
final class WindowsWidgetsTest extends AbstractPwaTestCase
{
    #[Test]
    public static function everyFunctionTheWidgetScriptAwaitsIsDeclaredInIt(): void
    {
        // Given
        $rule = self::getContainer()->get(WindowsWidgets::class);
        static::assertInstanceOf(WindowsWidgets::class, $rule);

        // When
        $script = $rule->process();
        preg_match_all('/\basync function (\w+)\(/', $script, $declared);
        preg_match_all('/\b(?:await|waitUntil\()\s*(\w+)\(/', $script, $called);

        // Then
        static::assertStringContainsString('self.registration.periodicSync.register(widget.definition.tag', $script);
        static::assertSame([], array_values(array_diff(array_unique($called[1]), $declared[1], ['fetch'])));
    }
}
