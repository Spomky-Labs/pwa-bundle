<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Normalizer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\Screenshot;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use SpomkyLabs\PwaBundle\Normalizer\ScreenshotNormalizer;
use stdClass;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class ScreenshotNormalizerTest extends TestCase
{
    #[Test]
    public function itSupportsScreenshotNormalization(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);
        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);
        $screenshot = new Screenshot();

        // When
        $supports = $normalizer->supportsNormalization($screenshot);

        // Then
        static::assertTrue($supports);
    }

    #[Test]
    public function itDoesNotSupportOtherTypes(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);
        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);
        $object = new stdClass();

        // When
        $supports = $normalizer->supportsNormalization($object);

        // Then
        static::assertFalse($supports);
    }

    #[Test]
    public function itReturnsCorrectSupportedTypes(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);
        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);

        // When
        $supportedTypes = $normalizer->getSupportedTypes(null);

        // Then
        static::assertArrayHasKey(Screenshot::class, $supportedTypes);
        static::assertTrue($supportedTypes[Screenshot::class]);
    }

    #[Test]
    public function itNormalizesScreenshotWithWidthAndHeight(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);

        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);

        $assetNormalizer = $this->createMock(NormalizerInterface::class);
        $assetNormalizer->method('normalize')
            ->willReturnCallback(static fn ($data): string => match (true) {
                $data instanceof Asset => '/assets/screenshot.png',
                default => 'Test Screenshot',
            });
        $normalizer->setNormalizer($assetNormalizer);

        $screenshot = new Screenshot();
        $screenshot->src = Asset::create('screenshot.png');
        $screenshot->width = 1920;
        $screenshot->height = 1080;
        $screenshot->formFactor = 'wide';
        $screenshot->label = 'Test Screenshot';
        $screenshot->platform = 'android';

        // When
        $result = $normalizer->normalize($screenshot);

        // Then
        static::assertIsArray($result);
        static::assertSame('/assets/screenshot.png', $result['src']);
        static::assertSame('1920x1080', $result['sizes']);
        static::assertSame('wide', $result['form_factor']);
        static::assertSame('Test Screenshot', $result['label']);
        static::assertSame('android', $result['platform']);
        static::assertArrayNotHasKey('type', $result); // No type specified
    }

    #[Test]
    public function itNormalizesScreenshotWithNarrowFormFactor(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);

        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);

        $assetNormalizer = $this->createMock(NormalizerInterface::class);
        $assetNormalizer->method('normalize')
            ->willReturn('/assets/screenshot.png');
        $normalizer->setNormalizer($assetNormalizer);

        $screenshot = new Screenshot();
        $screenshot->src = Asset::create('screenshot.png');
        $screenshot->width = 390;
        $screenshot->height = 844;
        $screenshot->formFactor = 'narrow';

        // When
        $result = $normalizer->normalize($screenshot);

        // Then
        static::assertIsArray($result);
        static::assertSame('390x844', $result['sizes']);
        static::assertSame('narrow', $result['form_factor']);
    }

    #[Test]
    public function itNormalizesScreenshotWithExplicitFormFactor(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);

        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);

        $assetNormalizer = $this->createMock(NormalizerInterface::class);
        $assetNormalizer->method('normalize')
            ->willReturn('/assets/screenshot.png');
        $normalizer->setNormalizer($assetNormalizer);

        $screenshot = new Screenshot();
        $screenshot->src = Asset::create('screenshot.png');
        $screenshot->width = 1920;
        $screenshot->height = 1080;
        $screenshot->formFactor = 'narrow'; // Override auto-detection

        // When
        $result = $normalizer->normalize($screenshot);

        // Then
        static::assertIsArray($result);
        static::assertSame('narrow', $result['form_factor']);
    }

    #[Test]
    public function itNormalizesScreenshotWithoutDimensions(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);

        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);

        $assetNormalizer = $this->createMock(NormalizerInterface::class);
        $assetNormalizer->method('normalize')
            ->willReturn('/screenshot.png');
        $normalizer->setNormalizer($assetNormalizer);

        $screenshot = new Screenshot();
        $screenshot->src = Asset::create('/screenshot.png');
        $screenshot->formFactor = 'wide';

        // When
        $result = $normalizer->normalize($screenshot);

        // Then
        static::assertIsArray($result);
        static::assertArrayNotHasKey('sizes', $result);
        static::assertSame('wide', $result['form_factor']);
    }

    #[Test]
    public function itFiltersNullValuesFromResult(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);

        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);

        $assetNormalizer = $this->createMock(NormalizerInterface::class);
        $assetNormalizer->method('normalize')
            ->willReturnCallback(static fn ($data): ?string => match (true) {
                $data instanceof Asset => '/assets/screenshot.png',
                default => null,
            });
        $normalizer->setNormalizer($assetNormalizer);

        $screenshot = new Screenshot();
        $screenshot->src = Asset::create('screenshot.png');
        $screenshot->width = 1920;
        $screenshot->height = 1080;

        // When
        $result = $normalizer->normalize($screenshot);

        // Then
        static::assertIsArray($result);
        static::assertArrayNotHasKey('label', $result);
        static::assertArrayNotHasKey('platform', $result);
    }

    #[Test]
    public function itNormalizesScreenshotWithoutDimensionsButWithImageProcessor(): void
    {
        // Given
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);

        $assetMapper->method('getAsset')
            ->willReturn(null);

        $normalizer = new ScreenshotNormalizer($assetMapper, $imageProcessor);

        $assetNormalizer = $this->createMock(NormalizerInterface::class);
        $assetNormalizer->method('normalize')
            ->willReturn('/assets/screenshot.png');
        $normalizer->setNormalizer($assetNormalizer);

        $screenshot = new Screenshot();
        $screenshot->src = Asset::create('screenshot.png');

        // When
        $result = $normalizer->normalize($screenshot);

        // Then
        static::assertIsArray($result);
        static::assertArrayNotHasKey('sizes', $result);
    }
}
