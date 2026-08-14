<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ServiceWorkerRule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\WorkboxDeprecatedHelpers;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class WorkboxDeprecatedHelpersTest extends TestCase
{
    #[Test]
    public function itReturnsEmptyStringWhenWorkboxIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = false;
        $workbox->keepDeprecatedHelpers = true;

        $rule = $this->createRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itReturnsEmptyStringWhenDeprecatedHelpersAreNotKept(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->keepDeprecatedHelpers = false;

        $rule = $this->createRule($workbox);

        // When
        $result = $rule->process(debug: true);

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itIsKeptByDefaultSoTheUpgradeToThisVersionIsSeamless(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        $rule = $this->createRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertTrue($workbox->keepDeprecatedHelpers);
        static::assertNotSame('', $result);
    }

    /**
     * Anything removed from WorkboxHelpers must still be emitted here, otherwise the
     * upgrade silently breaks the service worker sources that call them.
     */
    #[Test]
    #[DataProvider('dataDeprecatedHelpers')]
    public function itStillEmitsTheHelperMovedOutOfWorkboxHelpers(string $helper): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        $rule = $this->createRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringContainsString($helper, $result);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dataDeprecatedHelpers(): iterable
    {
        yield 'registerCacheFirst' => ['function registerCacheFirst('];
        yield 'registerMessageTask' => ['function registerMessageTask('];
        yield 'openBackgroundFetchDatabase' => ['async function openBackgroundFetchDatabase('];
        yield 'registerBackgroundFetchTask' => ['function registerBackgroundFetchTask('];
        yield 'registerPushTask' => ['function registerPushTask('];
        yield 'registerNotificationAction' => ['function registerNotificationAction('];
        yield 'structuredPushNotificationSupport' => ['const structuredPushNotificationSupport'];
        yield 'simplePushNotificationSupport' => ['function simplePushNotificationSupport('];
        yield 'registerPeriodicSyncTask' => ['function registerPeriodicSyncTask('];
        yield 'notifyPeriodicSyncClients' => ['function notifyPeriodicSyncClients('];
    }

    #[Test]
    public function itWarnsOnlyWhenADeprecatedHelperIsActuallyCalled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        $rule = $this->createRule($workbox);

        // When
        $result = $rule->process();

        // Then the warning sits in the function body, never at the top level: an
        // application that stopped calling them gets a silent service worker.
        static::assertStringContainsString('function reportDeprecatedHelper(name, replacement) {', $result);
        static::assertStringContainsString(
            "reportDeprecatedHelper('registerPushTask'",
            $result
        );
        static::assertStringContainsString('will be removed in 2.0.0', $result);
    }

    #[Test]
    public function itUsesTheConfiguredBackgroundFetchDatabaseName(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        $rule = $this->createRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringContainsString("self.idb.openDB('background-fetch-db'", $result);
    }

    #[Test]
    public function itIncludesDebugCommentsWhenDebugModeIsEnabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        $rule = $this->createRule($workbox);

        // When
        $result = $rule->process(debug: true);

        // Then
        static::assertStringContainsString('DEPRECATED HELPERS', $result);
        static::assertStringContainsString('keep_deprecated_helpers', $result);
    }

    #[Test]
    public function itDoesNotIncludeDebugCommentsWhenDebugModeIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;

        $rule = $this->createRule($workbox);

        // When
        $result = $rule->process(debug: false);

        // Then
        static::assertStringNotContainsString('DEPRECATED HELPERS', $result);
    }

    #[Test]
    public function itRunsAfterTheHelpersItCompletesAndBeforeTheRulesCallingIt(): void
    {
        // WorkboxImport (1024), WorkboxHelpers (1023) and NavigationPreload (1022) come
        // first; BackgroundFetchCache and the application source come after.
        $attributes = (new ReflectionClass(WorkboxDeprecatedHelpers::class))->getAttributes(AsTaggedItem::class);

        static::assertCount(1, $attributes);
        static::assertSame(1021, $attributes[0]->newInstance()->priority);
    }

    private function createRule(Workbox $workbox): WorkboxDeprecatedHelpers
    {
        $serviceWorker = new ServiceWorker();
        $serviceWorker->workbox = $workbox;

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($serviceWorker);

        $serviceWorkerBuilder = new ServiceWorkerBuilder($denormalizer, []);

        return new WorkboxDeprecatedHelpers($serviceWorkerBuilder);
    }
}
