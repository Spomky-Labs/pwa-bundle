<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\OfflineFallback;

/**
 * The fallbacks are cached once, when the worker installs. They must come from the network, not from the HTTP
 * cache, or a reinstalled worker keeps the copy the browser held before the deploy.
 *
 * @internal
 */
final class OfflineFallbackTest extends AbstractPwaTestCase
{
    #[Test]
    public static function theFallbacksAreFetchedBypassingTheHttpCache(): void
    {
        // Given
        $rule = self::getContainer()->get(OfflineFallback::class);
        static::assertInstanceOf(OfflineFallback::class, $rule);

        // When
        $script = $rule->process();

        // Then
        static::assertStringContainsString(
            'cache.addAll(["/offline.html"].map(url => new Request(url, { cache: \'reload\' })))',
            $script
        );
        static::assertStringContainsString('return await cache.match(fallbacks.pageFallback)', $script);
    }
}
