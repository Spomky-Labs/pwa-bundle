<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\PreloadResource;
use SpomkyLabs\PwaBundle\Dto\ResourceHints;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\ResourceHintsBuilder;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\WebLink\Link;

/**
 * @internal
 */
final class ResourceHintsBuilderTest extends TestCase
{
    #[Test]
    public function itReturnsEmptyArrayWhenDisabled(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = false;

        $builder = $this->createBuilder($hints, [
            'enabled' => false,
        ]);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertSame([], $links);
    }

    #[Test]
    public function itGeneratesPreconnectLinks(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preconnect = ['https://api.example.com', 'https://cdn.example.com/'];

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ]);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(2, $links);

        static::assertSame('https://api.example.com', $links[0]->getHref());
        static::assertContains(Link::REL_PRECONNECT, $links[0]->getRels());
        static::assertTrue($links[0]->getAttributes()['crossorigin']);

        static::assertSame('https://cdn.example.com', $links[1]->getHref());
        static::assertContains(Link::REL_PRECONNECT, $links[1]->getRels());
    }

    #[Test]
    public function itGeneratesDnsPrefetchLinks(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->dnsPrefetch = ['https://analytics.example.com', 'https://tracking.example.com/'];

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ]);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(2, $links);

        static::assertSame('https://analytics.example.com', $links[0]->getHref());
        static::assertContains(Link::REL_DNS_PREFETCH, $links[0]->getRels());

        static::assertSame('https://tracking.example.com', $links[1]->getHref());
        static::assertContains(Link::REL_DNS_PREFETCH, $links[1]->getRels());
    }

    #[Test]
    public function itGeneratesPreloadLinksWithCorrectAttributes(): void
    {
        // Given
        $fontPreload = new PreloadResource();
        $fontPreload->href = '/fonts/custom.woff2';
        $fontPreload->as = 'font';
        $fontPreload->type = 'font/woff2';
        $fontPreload->crossorigin = 'anonymous';

        $stylePreload = new PreloadResource();
        $stylePreload->href = '/css/critical.css';
        $stylePreload->as = 'style';
        $stylePreload->fetchPriority = 'high';

        $imagePreload = new PreloadResource();
        $imagePreload->href = '/images/hero.webp';
        $imagePreload->as = 'image';
        $imagePreload->media = '(min-width: 768px)';

        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preload = [$fontPreload, $stylePreload, $imagePreload];

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ]);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(3, $links);

        // Font preload
        static::assertSame('/fonts/custom.woff2', $links[0]->getHref());
        static::assertContains(Link::REL_PRELOAD, $links[0]->getRels());
        static::assertSame('font', $links[0]->getAttributes()['as']);
        static::assertSame('font/woff2', $links[0]->getAttributes()['type']);
        static::assertSame('anonymous', $links[0]->getAttributes()['crossorigin']);

        // Style preload
        static::assertSame('/css/critical.css', $links[1]->getHref());
        static::assertContains(Link::REL_PRELOAD, $links[1]->getRels());
        static::assertSame('style', $links[1]->getAttributes()['as']);
        static::assertSame('high', $links[1]->getAttributes()['fetchpriority']);

        // Image preload with media query
        static::assertSame('/images/hero.webp', $links[2]->getHref());
        static::assertContains(Link::REL_PRELOAD, $links[2]->getRels());
        static::assertSame('image', $links[2]->getAttributes()['as']);
        static::assertSame('(min-width: 768px)', $links[2]->getAttributes()['media']);
    }

    #[Test]
    public function itAutoAddsCrossoriginForFontPreloads(): void
    {
        // Given
        $fontPreload = new PreloadResource();
        $fontPreload->href = '/fonts/sans.woff2';
        $fontPreload->as = 'font';

        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preload = [$fontPreload];

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ]);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(1, $links);
        static::assertSame('anonymous', $links[0]->getAttributes()['crossorigin']);
    }

    #[Test]
    public function itAutoDetectsWorkboxCdnOrigins(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = true;

        $builder = $this->createBuilder(
            $hints,
            [
                'enabled' => true,
            ],
            [
                'workbox' => [
                    'enabled' => true,
                    'use_cdn' => true,
                ],
            ]
        );

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(2, $links);

        $hrefs = array_map(fn (Link $link) => $link->getHref(), $links);
        static::assertContains('https://storage.googleapis.com', $hrefs);
        static::assertContains('https://cdn.jsdelivr.net', $hrefs);
    }

    #[Test]
    public function itAutoDetectsGoogleFontsOrigins(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = true;

        $builder = $this->createBuilder(
            $hints,
            [
                'enabled' => true,
            ],
            [
                'workbox' => [
                    'enabled' => true,
                    'use_cdn' => false,
                    'google_fonts' => [
                        'enabled' => true,
                    ],
                ],
            ]
        );

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(2, $links);

        $hrefs = array_map(fn (Link $link) => $link->getHref(), $links);
        static::assertContains('https://fonts.googleapis.com', $hrefs);
        static::assertContains('https://fonts.gstatic.com', $hrefs);
    }

    #[Test]
    public function itDeduplicatesPreconnectOrigins(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = true;
        $hints->preconnect = ['https://fonts.googleapis.com'];

        $builder = $this->createBuilder(
            $hints,
            [
                'enabled' => true,
            ],
            [
                'workbox' => [
                    'enabled' => true,
                    'google_fonts' => [
                        'enabled' => true,
                    ],
                ],
            ]
        );

        // When
        $links = $builder->getLinks();

        // Then
        $hrefs = array_map(fn (Link $link) => $link->getHref(), $links);
        $googleFontsCount = array_count_values($hrefs)['https://fonts.googleapis.com'] ?? 0;
        static::assertSame(1, $googleFontsCount);
    }

    #[Test]
    public function itGeneratesCorrectHtml(): void
    {
        // Given
        $fontPreload = new PreloadResource();
        $fontPreload->href = '/fonts/custom.woff2';
        $fontPreload->as = 'font';
        $fontPreload->type = 'font/woff2';

        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preconnect = ['https://api.example.com'];
        $hints->dnsPrefetch = ['https://analytics.example.com'];
        $hints->preload = [$fontPreload];

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ]);

        // When
        $html = $builder->generateHtml();

        // Then
        static::assertStringContainsString('rel="preconnect"', $html);
        static::assertStringContainsString('href="https://api.example.com"', $html);
        static::assertStringContainsString('rel="dns-prefetch"', $html);
        static::assertStringContainsString('href="https://analytics.example.com"', $html);
        static::assertStringContainsString('rel="preload"', $html);
        static::assertStringContainsString('href="/fonts/custom.woff2"', $html);
        static::assertStringContainsString('as="font"', $html);
        static::assertStringContainsString('type="font/woff2"', $html);
    }

    #[Test]
    public function itReturnsEmptyHtmlWhenDisabled(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = false;

        $builder = $this->createBuilder($hints, [
            'enabled' => false,
        ]);

        // When
        $html = $builder->generateHtml();

        // Then
        static::assertSame('', $html);
    }

    #[Test]
    public function itResolvesAssetMapperPaths(): void
    {
        // Given
        $fontPreload = new PreloadResource();
        $fontPreload->href = 'fonts/inter-var.woff2'; // Asset Mapper logical path (no leading /)
        $fontPreload->as = 'font';

        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preload = [$fontPreload];

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper
            ->method('getPublicPath')
            ->with('fonts/inter-var.woff2')
            ->willReturn('/assets/fonts/inter-var-abc123.woff2');

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ], [], $assetMapper);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(1, $links);
        static::assertSame('/assets/fonts/inter-var-abc123.woff2', $links[0]->getHref());
    }

    #[Test]
    public function itKeepsAbsolutePathsUnchanged(): void
    {
        // Given
        $fontPreload = new PreloadResource();
        $fontPreload->href = '/fonts/custom.woff2'; // Absolute path (starts with /)
        $fontPreload->as = 'font';

        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preload = [$fontPreload];

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper
            ->expects(static::never())
            ->method('getPublicPath');

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ], [], $assetMapper);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(1, $links);
        static::assertSame('/fonts/custom.woff2', $links[0]->getHref());
    }

    #[Test]
    public function itKeepsAbsoluteUrlsUnchanged(): void
    {
        // Given
        $fontPreload = new PreloadResource();
        $fontPreload->href = 'https://cdn.example.com/fonts/custom.woff2';
        $fontPreload->as = 'font';

        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preload = [$fontPreload];

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper
            ->expects(static::never())
            ->method('getPublicPath');

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ], [], $assetMapper);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(1, $links);
        static::assertSame('https://cdn.example.com/fonts/custom.woff2', $links[0]->getHref());
    }

    #[Test]
    public function itFallsBackToSlashPrefixWhenAssetNotFound(): void
    {
        // Given
        $fontPreload = new PreloadResource();
        $fontPreload->href = 'fonts/unknown.woff2';
        $fontPreload->as = 'font';

        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preload = [$fontPreload];

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper
            ->method('getPublicPath')
            ->with('fonts/unknown.woff2')
            ->willReturn(null);

        $builder = $this->createBuilder($hints, [
            'enabled' => true,
        ], [], $assetMapper);

        // When
        $links = $builder->getLinks();

        // Then
        static::assertCount(1, $links);
        static::assertSame('/fonts/unknown.woff2', $links[0]->getHref());
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $workboxConfig
     */
    private function createBuilder(
        ResourceHints $hints,
        array $config,
        array $workboxConfig = [],
        ?AssetMapperInterface $assetMapper = null
    ): ResourceHintsBuilder {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($hints);

        $assetMapper ??= $this->createMock(AssetMapperInterface::class);

        return new ResourceHintsBuilder($denormalizer, $assetMapper, $config, $workboxConfig, new BasePathResolver());
    }
}
