<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\FaviconsBuilder;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use SpomkyLabs\PwaBundle\Service\ResourceHintsBuilder;
use SpomkyLabs\PwaBundle\Service\SpeculationRulesBuilder;
use SpomkyLabs\PwaBundle\Twig\PwaRuntime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class MobileWebAppCapableTest extends KernelTestCase
{
    #[Test]
    public function itGeneratesMobileWebAppCapableMetaTagForStandaloneDisplay(): void
    {
        // Given (using default test configuration which has display: 'standalone')
        static::bootKernel();
        $runtime = static::getContainer()->get(PwaRuntime::class);

        // When
        $output = $runtime->load(
            injectThemeColor: false,
            injectFavicons: false,
            injectSW: false,
            injectResourceHints: false,
            injectSpeculationRules: false
        );

        // Then
        static::assertStringContainsString('<meta name="mobile-web-app-capable" content="yes">', (string) $output);
    }

    #[Test]
    public function itGeneratesMobileWebAppCapableMetaTagForFullscreenDisplay(): void
    {
        // Given
        static::bootKernel();

        $runtime = $this->createRuntimeWithDisplayMode('fullscreen');

        // When
        $output = $runtime->load(
            injectThemeColor: false,
            injectFavicons: false,
            injectSW: false,
            injectResourceHints: false,
            injectSpeculationRules: false
        );

        // Then
        static::assertStringContainsString('<meta name="mobile-web-app-capable" content="yes">', $output);
    }

    #[Test]
    public function itGeneratesMobileWebAppCapableMetaTagForMinimalUiDisplay(): void
    {
        // Given
        static::bootKernel();

        $runtime = $this->createRuntimeWithDisplayMode('minimal-ui');

        // When
        $output = $runtime->load(
            injectThemeColor: false,
            injectFavicons: false,
            injectSW: false,
            injectResourceHints: false,
            injectSpeculationRules: false
        );

        // Then
        static::assertStringContainsString('<meta name="mobile-web-app-capable" content="yes">', $output);
    }

    #[Test]
    public function itDoesNotGenerateMobileWebAppCapableMetaTagForBrowserDisplay(): void
    {
        // Given
        static::bootKernel();

        $runtime = $this->createRuntimeWithDisplayMode('browser');

        // When
        $output = $runtime->load(
            injectThemeColor: false,
            injectFavicons: false,
            injectSW: false,
            injectResourceHints: false,
            injectSpeculationRules: false
        );

        // Then
        static::assertStringNotContainsString('<meta name="mobile-web-app-capable" content="yes">', $output);
    }

    #[Test]
    public function itDoesNotGenerateMobileWebAppCapableMetaTagWhenDisplayIsNotSet(): void
    {
        // Given
        static::bootKernel();

        $runtime = $this->createRuntimeWithDisplayMode(null);

        // When
        $output = $runtime->load(
            injectThemeColor: false,
            injectFavicons: false,
            injectSW: false,
            injectResourceHints: false,
            injectSpeculationRules: false
        );

        // Then
        static::assertStringNotContainsString('<meta name="mobile-web-app-capable" content="yes">', $output);
    }

    private function createRuntimeWithDisplayMode(?string $displayMode): PwaRuntime
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->method('denormalize')
            ->willReturnCallback(function ($data, string $type) use ($displayMode): mixed {
                if ($type === Manifest::class) {
                    $manifest = new Manifest();
                    $manifest->enabled = true;
                    $manifest->display = $displayMode;
                    return $manifest;
                }
                if ($type === Favicons::class) {
                    $favicons = new Favicons();
                    $favicons->enabled = false;
                    return $favicons;
                }
                return null;
            });

        $manifestBuilder = new ManifestBuilder($denormalizer, []);

        return new PwaRuntime(
            static::getContainer()->get('asset_mapper'),
            $manifestBuilder,
            static::getContainer()->get(FaviconsBuilder::class),
            static::getContainer()->get(FaviconsCompiler::class),
            '/manifest.json',
            static::getContainer()->get('request_stack'),
            static::getContainer()->get(ResourceHintsBuilder::class),
            static::getContainer()->get(SpeculationRulesBuilder::class),
            static::getContainer()->get(BasePathResolver::class)
        );
    }
}
