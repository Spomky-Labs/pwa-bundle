<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Attribute;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Attribute\Screenshot;

/**
 * @internal
 */
final class ScreenshotTest extends TestCase
{
    #[Test]
    public function itCanBeInstantiatedWithMinimalParameters(): void
    {
        $screenshot = new Screenshot(sizes: ['fhd', 'iphone-14']);

        static::assertSame(['fhd', 'iphone-14'], $screenshot->sizes);
        static::assertSame([], $screenshot->parameters);
        static::assertNull($screenshot->name);
        static::assertNull($screenshot->label);
        static::assertNull($screenshot->platform);
        static::assertNull($screenshot->output);
        static::assertNull($screenshot->format);
    }

    #[Test]
    public function itCanBeInstantiatedWithAllParameters(): void
    {
        $screenshot = new Screenshot(
            sizes: ['fhd', 'ipad/LP', '1920x1080'],
            parameters: [
                '_locale' => 'fr',
                'id' => 42,
            ],
            name: 'homepage',
            label: 'Application homepage',
            platform: 'windows',
            output: '/custom/output',
            format: 'webp'
        );

        static::assertSame(['fhd', 'ipad/LP', '1920x1080'], $screenshot->sizes);
        static::assertSame([
            '_locale' => 'fr',
            'id' => 42,
        ], $screenshot->parameters);
        static::assertSame('homepage', $screenshot->name);
        static::assertSame('Application homepage', $screenshot->label);
        static::assertSame('windows', $screenshot->platform);
        static::assertSame('/custom/output', $screenshot->output);
        static::assertSame('webp', $screenshot->format);
    }

    #[Test]
    public function itSupportsOrientationSelectorsInSizes(): void
    {
        $screenshot = new Screenshot(sizes: ['ipad/L', 'iphone-14/P', 'fhd/LP']);

        static::assertContains('ipad/L', $screenshot->sizes);
        static::assertContains('iphone-14/P', $screenshot->sizes);
        static::assertContains('fhd/LP', $screenshot->sizes);
    }

    #[Test]
    public function itSupportsRouteParameters(): void
    {
        $screenshot = new Screenshot(
            sizes: ['desktop-lg'],
            parameters: [
                '_locale' => 'en',
                'category' => 'tech',
            ]
        );

        static::assertArrayHasKey('_locale', $screenshot->parameters);
        static::assertArrayHasKey('category', $screenshot->parameters);
        static::assertSame('en', $screenshot->parameters['_locale']);
        static::assertSame('tech', $screenshot->parameters['category']);
    }

    #[Test]
    public function itHasEmptyParametersByDefault(): void
    {
        $screenshot = new Screenshot(sizes: ['fhd']);

        static::assertIsArray($screenshot->parameters);
        static::assertEmpty($screenshot->parameters);
    }
}
