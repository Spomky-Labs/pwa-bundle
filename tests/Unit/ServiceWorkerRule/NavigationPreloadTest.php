<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ServiceWorkerRule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\NavigationPreload;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class NavigationPreloadTest extends TestCase
{
    #[Test]
    public function itReturnsEmptyStringWhenWorkboxIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = false;
        $workbox->navigationPreload = true;

        $rule = $this->createNavigationPreloadRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itReturnsEmptyStringWhenNavigationPreloadIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->navigationPreload = false;

        $rule = $this->createNavigationPreloadRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertSame('', $result);
    }

    #[Test]
    public function itReturnsJavaScriptCodeWhenNavigationPreloadIsEnabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->navigationPreload = true;

        $rule = $this->createNavigationPreloadRule($workbox);

        // When
        $result = $rule->process();

        // Then
        static::assertStringContainsString('workbox.navigationPreload.enable();', $result);
    }

    #[Test]
    public function itIncludesDebugCommentsWhenDebugModeIsEnabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->navigationPreload = true;

        $rule = $this->createNavigationPreloadRule($workbox);

        // When
        $result = $rule->process(debug: true);

        // Then
        static::assertStringContainsString('NAVIGATION PRELOAD', $result);
        static::assertStringContainsString('workbox.navigationPreload.enable();', $result);
        static::assertStringContainsString('END NAVIGATION PRELOAD', $result);
    }

    #[Test]
    public function itDoesNotIncludeDebugCommentsWhenDebugModeIsDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->navigationPreload = true;

        $rule = $this->createNavigationPreloadRule($workbox);

        // When
        $result = $rule->process(debug: false);

        // Then
        static::assertStringNotContainsString('NAVIGATION PRELOAD', $result);
        static::assertStringContainsString('workbox.navigationPreload.enable();', $result);
    }

    #[Test]
    public function itHasCorrectPriority(): void
    {
        // Navigation Preload should run after WorkboxImport (1024) and WorkboxHelpers (1023)
        // but before cache strategies
        $attributes = (new ReflectionClass(NavigationPreload::class))->getAttributes(AsTaggedItem::class);

        static::assertCount(1, $attributes);
        static::assertSame(1022, $attributes[0]->newInstance()->priority);
    }

    private function createNavigationPreloadRule(Workbox $workbox): NavigationPreload
    {
        $serviceWorker = new ServiceWorker();
        $serviceWorker->workbox = $workbox;

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($serviceWorker);

        $serviceWorkerBuilder = new ServiceWorkerBuilder($denormalizer, []);

        return new NavigationPreload($serviceWorkerBuilder);
    }
}
