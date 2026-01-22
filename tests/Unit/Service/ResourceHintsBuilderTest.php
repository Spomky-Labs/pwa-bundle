<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\PreloadResource;
use SpomkyLabs\PwaBundle\Dto\ResourceHints;
use SpomkyLabs\PwaBundle\Service\ResourceHintsBuilder;
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

        $builder = $this->createBuilder($hints, ['enabled' => false]);

        // When
        $links = $builder->getLinks();

        // Then
        self::assertSame([], $links);
    }

    #[Test]
    public function itGeneratesPreconnectLinks(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preconnect = [
            'https://api.example.com',
            'https://cdn.example.com/',
        ];

        $builder = $this->createBuilder($hints, ['enabled' => true]);

        // When
        $links = $builder->getLinks();

        // Then
        self::assertCount(2, $links);

        self::assertSame('https://api.example.com', $links[0]->getHref());
        self::assertContains(Link::REL_PRECONNECT, $links[0]->getRels());
        self::assertTrue($links[0]->getAttributes()['crossorigin']);

        self::assertSame('https://cdn.example.com', $links[1]->getHref());
        self::assertContains(Link::REL_PRECONNECT, $links[1]->getRels());
    }

    #[Test]
    public function itGeneratesDnsPrefetchLinks(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->dnsPrefetch = [
            'https://analytics.example.com',
            'https://tracking.example.com/',
        ];

        $builder = $this->createBuilder($hints, ['enabled' => true]);

        // When
        $links = $builder->getLinks();

        // Then
        self::assertCount(2, $links);

        self::assertSame('https://analytics.example.com', $links[0]->getHref());
        self::assertContains(Link::REL_DNS_PREFETCH, $links[0]->getRels());

        self::assertSame('https://tracking.example.com', $links[1]->getHref());
        self::assertContains(Link::REL_DNS_PREFETCH, $links[1]->getRels());
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

        $builder = $this->createBuilder($hints, ['enabled' => true]);

        // When
        $links = $builder->getLinks();

        // Then
        self::assertCount(3, $links);

        // Font preload
        self::assertSame('/fonts/custom.woff2', $links[0]->getHref());
        self::assertContains(Link::REL_PRELOAD, $links[0]->getRels());
        self::assertSame('font', $links[0]->getAttributes()['as']);
        self::assertSame('font/woff2', $links[0]->getAttributes()['type']);
        self::assertSame('anonymous', $links[0]->getAttributes()['crossorigin']);

        // Style preload
        self::assertSame('/css/critical.css', $links[1]->getHref());
        self::assertContains(Link::REL_PRELOAD, $links[1]->getRels());
        self::assertSame('style', $links[1]->getAttributes()['as']);
        self::assertSame('high', $links[1]->getAttributes()['fetchpriority']);

        // Image preload with media query
        self::assertSame('/images/hero.webp', $links[2]->getHref());
        self::assertContains(Link::REL_PRELOAD, $links[2]->getRels());
        self::assertSame('image', $links[2]->getAttributes()['as']);
        self::assertSame('(min-width: 768px)', $links[2]->getAttributes()['media']);
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

        $builder = $this->createBuilder($hints, ['enabled' => true]);

        // When
        $links = $builder->getLinks();

        // Then
        self::assertCount(1, $links);
        self::assertSame('anonymous', $links[0]->getAttributes()['crossorigin']);
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
            ['enabled' => true],
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
        self::assertCount(2, $links);

        $hrefs = array_map(fn (Link $link) => $link->getHref(), $links);
        self::assertContains('https://storage.googleapis.com', $hrefs);
        self::assertContains('https://cdn.jsdelivr.net', $hrefs);
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
            ['enabled' => true],
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
        self::assertCount(2, $links);

        $hrefs = array_map(fn (Link $link) => $link->getHref(), $links);
        self::assertContains('https://fonts.googleapis.com', $hrefs);
        self::assertContains('https://fonts.gstatic.com', $hrefs);
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
            ['enabled' => true],
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
        self::assertSame(1, $googleFontsCount);
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

        $builder = $this->createBuilder($hints, ['enabled' => true]);

        // When
        $html = $builder->generateHtml();

        // Then
        self::assertStringContainsString('rel="preconnect"', $html);
        self::assertStringContainsString('href="https://api.example.com"', $html);
        self::assertStringContainsString('rel="dns-prefetch"', $html);
        self::assertStringContainsString('href="https://analytics.example.com"', $html);
        self::assertStringContainsString('rel="preload"', $html);
        self::assertStringContainsString('href="/fonts/custom.woff2"', $html);
        self::assertStringContainsString('as="font"', $html);
        self::assertStringContainsString('type="font/woff2"', $html);
    }

    #[Test]
    public function itReturnsEmptyHtmlWhenDisabled(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = false;

        $builder = $this->createBuilder($hints, ['enabled' => false]);

        // When
        $html = $builder->generateHtml();

        // Then
        self::assertSame('', $html);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $workboxConfig
     */
    private function createBuilder(
        ResourceHints $hints,
        array $config,
        array $workboxConfig = []
    ): ResourceHintsBuilder {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($hints);

        return new ResourceHintsBuilder($denormalizer, $config, $workboxConfig);
    }
}
