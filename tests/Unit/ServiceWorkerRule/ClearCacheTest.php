<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ServiceWorkerRule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\ClearCache;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\WorkboxHelpers;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class ClearCacheTest extends TestCase
{
    #[Test]
    public function itReturnsEmptyStringWhenWorkboxIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = false;

        // When
        $result = $this->createRule($workbox)
            ->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itReturnsEmptyStringWhenClearCacheIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->clearCache = false;

        // When
        $result = $this->createRule($workbox)
            ->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itPurgesTheCachesRegisteredByTheBundle(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        // When
        $result = $this->createRule($workbox)
            ->process();

        // Then
        static::assertStringContainsString('const keys = await caches.keys();', $result);
        static::assertStringContainsString('keys.filter(k => usedCacheNames.has(k))', $result);
        static::assertStringContainsString('caches.delete(k)', $result);
    }

    /**
     * Caches opened by the application are unknown to registerCacheName(), so they are
     * contributed at install time instead of declared upfront.
     */
    #[Test]
    public function itAsksTheApplicationWhichOtherCachesToPurge(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        // When
        $result = $this->createRule($workbox)
            ->process();

        // Then
        static::assertStringContainsString('for (const task of clearCacheListeners) {', $result);
        static::assertStringContainsString('for (const name of (await task(keys)) ?? []) {', $result);
        static::assertStringContainsString('doomed.add(name);', $result);
    }

    /**
     * A listener throwing must not take the install event down with it, nor stop the ones
     * registered after it.
     */
    #[Test]
    public function itSurvivesAListenerThatThrows(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        // When
        $result = $this->createRule($workbox)
            ->process();

        // Then
        static::assertStringContainsString('} catch (e) {', $result);
        static::assertStringContainsString("console.error('A clear cache listener failed', e);", $result);
    }

    #[Test]
    public function itRunsFirstSoTheOtherInstallTasksSeeACleanState(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        // When
        $result = $this->createRule($workbox)
            ->process();

        // Then SkipWaiting is at 5, OfflineFallback at 10 and the cache strategies at 100
        static::assertStringContainsString('}, 0);', $result);
    }

    /**
     * The registry is declared by WorkboxHelpers, which runs at 1023 while this rule sits at
     * the default priority, so the constant always exists by the time it is read.
     */
    #[Test]
    public function itReliesOnARegistryDeclaredByTheHelpers(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        // When
        $helpers = $this->createHelpers($workbox)
            ->process();

        // Then
        static::assertStringContainsString('const clearCacheListeners = [];', $helpers);
        static::assertStringContainsString('function registerClearCacheListener(callback) {', $helpers);
    }

    #[Test]
    public function itIncludesDebugCommentsWhenDebugModeIsEnabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        // When
        $result = $this->createRule($workbox)
            ->process(debug: true);

        // Then
        static::assertStringContainsString('CACHE CLEAR', $result);
        static::assertStringContainsString('registerClearCacheListener', $result);
        static::assertStringContainsString('END CACHE CLEAR', $result);
    }

    private function createRule(Workbox $workbox): ClearCache
    {
        return new ClearCache($this->createBuilder($workbox));
    }

    private function createHelpers(Workbox $workbox): WorkboxHelpers
    {
        return new WorkboxHelpers($this->createBuilder($workbox));
    }

    private function createBuilder(Workbox $workbox): ServiceWorkerBuilder
    {
        $serviceWorker = new ServiceWorker();
        $serviceWorker->workbox = $workbox;

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($serviceWorker);

        return new ServiceWorkerBuilder($denormalizer, []);
    }
}
