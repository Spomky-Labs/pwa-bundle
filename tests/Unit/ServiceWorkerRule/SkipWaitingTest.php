<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ServiceWorkerRule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\SkipWaiting;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @see https://github.com/Spomky-Labs/pwa-bundle/issues/496
 *
 * @internal
 */
final class SkipWaitingTest extends TestCase
{
    #[Test]
    #[DataProvider('dataWorkboxStates')]
    public function itReturnsEmptyStringWhenSkipWaitingIsDisabled(bool $workboxEnabled): void
    {
        // Given
        $rule = $this->createSkipWaitingRule(skipWaiting: false, workboxEnabled: $workboxEnabled);

        // When
        $result = $rule->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itRegistersAnInstallTaskWhenWorkboxIsEnabled(): void
    {
        // Given
        $rule = $this->createSkipWaitingRule(skipWaiting: true, workboxEnabled: true);

        // When
        $result = $rule->process();

        // Then
        $expected = <<<'JS'
registerInstallTask(() => self.skipWaiting(), 5);
self.addEventListener("activate", function (event) {
  event.waitUntil(self.clients.claim());
});

JS;
        static::assertSame($expected, $result);
    }

    #[Test]
    public function itUsesAPlainInstallListenerWhenWorkboxIsDisabled(): void
    {
        // Given
        $rule = $this->createSkipWaitingRule(skipWaiting: true, workboxEnabled: false);

        // When
        $result = $rule->process();

        // Then
        $expected = <<<'JS'
self.addEventListener("install", function (event) {
  event.waitUntil(self.skipWaiting());
});
self.addEventListener("activate", function (event) {
  event.waitUntil(self.clients.claim());
});

JS;
        static::assertSame($expected, $result);
        static::assertStringNotContainsString('registerInstallTask', $result);
    }

    #[Test]
    #[DataProvider('dataWorkboxStates')]
    public function itIncludesDebugCommentsWhenDebugModeIsEnabled(bool $workboxEnabled): void
    {
        // Given
        $rule = $this->createSkipWaitingRule(skipWaiting: true, workboxEnabled: $workboxEnabled);

        // When
        $result = $rule->process(debug: true);

        // Then
        static::assertStringContainsString('SKIP WAITING', $result);
        static::assertStringContainsString('self.skipWaiting()', $result);
        static::assertStringContainsString('self.clients.claim()', $result);
        static::assertStringContainsString('END SKIP WAITING', $result);
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function dataWorkboxStates(): iterable
    {
        yield 'Workbox enabled' => [true];
        yield 'Workbox disabled' => [false];
    }

    private function createSkipWaitingRule(bool $skipWaiting, bool $workboxEnabled): SkipWaiting
    {
        $workbox = new Workbox();
        $workbox->enabled = $workboxEnabled;

        $serviceWorker = new ServiceWorker();
        $serviceWorker->skipWaiting = $skipWaiting;
        $serviceWorker->workbox = $workbox;

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($serviceWorker);

        $serviceWorkerBuilder = new ServiceWorkerBuilder($denormalizer, []);

        return new SkipWaiting($serviceWorkerBuilder);
    }
}
