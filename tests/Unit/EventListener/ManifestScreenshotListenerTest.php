<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Event\PreManifestCompileEvent;
use SpomkyLabs\PwaBundle\EventListener\ManifestScreenshotListener;
use SpomkyLabs\PwaBundle\Service\ScreenshotAttributeCollector;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 *
 * Note: ManifestScreenshotListener now automatically discovers screenshots
 * by detecting #[Screenshot] attributes on controller methods.
 * Comprehensive testing of this behavior requires functional tests with
 * real controllers and routes.
 */
final class ManifestScreenshotListenerTest extends TestCase
{
    #[Test]
    public function itDoesNothingWhenNoConfigurationsExist(): void
    {
        $attributeCollector = $this->createMock(ScreenshotAttributeCollector::class);
        $attributeCollector->method('collect')
            ->willReturn([
                'configurations' => [],
            ]);

        $router = $this->createMock(RouterInterface::class);
        $assetMapper = $this->createMock(AssetMapperInterface::class);

        $listener = new ManifestScreenshotListener($attributeCollector, $router, $assetMapper);

        $manifest = new Manifest();
        $event = new PreManifestCompileEvent($manifest);

        $listener($event);

        static::assertCount(0, $manifest->screenshots);
    }

    #[Test]
    public function itDoesNothingWhenRoutesHaveNoScreenshotAttributes(): void
    {
        $attributeCollector = $this->createMock(ScreenshotAttributeCollector::class);
        $attributeCollector->method('collect')
            ->willReturn([
                'configurations' => [],
            ]);

        $router = $this->createMock(RouterInterface::class);
        $assetMapper = $this->createMock(AssetMapperInterface::class);

        $listener = new ManifestScreenshotListener($attributeCollector, $router, $assetMapper);

        $manifest = new Manifest();
        $event = new PreManifestCompileEvent($manifest);

        $listener($event);

        // No screenshots should be added since the collector returns no configurations
        static::assertCount(0, $manifest->screenshots);
    }
}
