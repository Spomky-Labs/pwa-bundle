<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use function array_slice;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerCompiler;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\NavigationPreload;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\ServiceWorkerRuleInterface;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\WorkboxHelpers;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\WorkboxImport;

/**
 * The Workbox import must be written first, then its helpers, then the navigation preload. That
 * relative order comes from the #[AsTaggedItem] priorities carried by the rules themselves.
 *
 * @internal
 */
final class ServiceWorkerRuleOrderTest extends AbstractPwaTestCase
{
    #[Test]
    public static function theHighestPriorityRulesAreAppliedFirst(): void
    {
        // Given
        $compiler = self::getContainer()->get(ServiceWorkerCompiler::class);
        static::assertInstanceOf(ServiceWorkerCompiler::class, $compiler);

        // When
        /** @var iterable<ServiceWorkerRuleInterface> $rules */
        $rules = (new ReflectionProperty($compiler, 'serviceworkerRules'))->getValue($compiler);
        $classes = [];
        foreach ($rules as $rule) {
            $classes[] = $rule::class;
        }

        // Then
        static::assertSame(
            [WorkboxImport::class, WorkboxHelpers::class, NavigationPreload::class],
            array_slice($classes, 0, 3)
        );
    }
}
