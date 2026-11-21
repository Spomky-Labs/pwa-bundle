<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ServiceWorkerRule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SpomkyLabs\PwaBundle\Dto\BackgroundFetch;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Url;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\BackgroundFetchCache;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

/**
 * The rule calls registerBackgroundFetchTask(), openBackgroundFetchDatabase() and bgFetchMetadata, which only
 * exist when WorkboxHelpers emits them, that is when Workbox is enabled.
 *
 * @see https://github.com/Spomky-Labs/pwa-bundle/issues/496
 *
 * @internal
 */
final class BackgroundFetchCacheTest extends TestCase
{
    #[Test]
    public function itReturnsEmptyStringWhenWorkboxIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = false;
        $workbox->backgroundFetch = $this->createBackgroundFetch();

        $rule = $this->createBackgroundFetchCacheRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itLogsWhyTheRuleIsSkippedWhenWorkboxIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = false;
        $workbox->backgroundFetch = $this->createBackgroundFetch();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::stringContains('Workbox is disabled'));

        $rule = $this->createBackgroundFetchCacheRule($workbox);
        $rule->setLogger($logger);

        // When
        $rule->process();
    }

    #[Test]
    public function itReturnsEmptyStringWhenBackgroundFetchIsNotConfigured(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->backgroundFetch = null;

        $rule = $this->createBackgroundFetchCacheRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itReturnsEmptyStringWhenBackgroundFetchIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->backgroundFetch = $this->createBackgroundFetch();
        $workbox->backgroundFetch->enabled = false;

        $rule = $this->createBackgroundFetchCacheRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itRegistersTheBackgroundFetchTasksWhenWorkboxIsEnabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->backgroundFetch = $this->createBackgroundFetch();

        $rule = $this->createBackgroundFetchCacheRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringContainsString("registerBackgroundFetchTask('click'", $result);
        static::assertStringContainsString("clients.openWindow('/downloads/done')", $result);
        static::assertStringContainsString("clients.openWindow('/downloads/progress')", $result);
        static::assertStringContainsString("registerBackgroundFetchTask('success'", $result);
        static::assertStringContainsString('openBackgroundFetchDatabase()', $result);
        static::assertStringContainsString("registerBackgroundFetchTask('fail'", $result);
    }

    #[Test]
    public function itTranslatesTheMessagesWithTheConfiguredDomain(): void
    {
        // Given
        $backgroundFetch = new BackgroundFetch();
        $backgroundFetch->enabled = true;
        $backgroundFetch->successMessage = 'app.background_fetch.success';
        $backgroundFetch->failureMessage = 'app.background_fetch.failure';
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'app.background_fetch.success' => 'Download complete',
            'app.background_fetch.failure' => 'Download failed',
        ], 'en', 'app');
        $rule = new BackgroundFetchCache(
            $this->createBuilder($backgroundFetch),
            static::createStub(RouterInterface::class),
            $translator,
            'app'
        );

        // When
        $result = $rule->process();

        // Then
        static::assertStringContainsString('Download complete', $result);
        static::assertStringContainsString('Download failed', $result);
    }

    private function createBackgroundFetch(): BackgroundFetch
    {
        $backgroundFetch = new BackgroundFetch();
        $backgroundFetch->enabled = true;
        $backgroundFetch->successUrl = Url::create('/downloads/done');
        $backgroundFetch->progressUrl = Url::create('/downloads/progress');

        return $backgroundFetch;
    }

    private function createBackgroundFetchCacheRule(Workbox $workbox): BackgroundFetchCache
    {
        $serviceWorker = new ServiceWorker();
        $serviceWorker->workbox = $workbox;

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($serviceWorker);

        $serviceWorkerBuilder = new ServiceWorkerBuilder($denormalizer, []);

        return new BackgroundFetchCache($serviceWorkerBuilder, static::createStub(RouterInterface::class));
    }

    private function createBuilder(BackgroundFetch $backgroundFetch): ServiceWorkerBuilder
    {
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->backgroundFetch = $backgroundFetch;
        $serviceWorker = new ServiceWorker();
        $serviceWorker->workbox = $workbox;

        $denormalizer = static::createStub(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($serviceWorker);

        return new ServiceWorkerBuilder($denormalizer, []);
    }
}
