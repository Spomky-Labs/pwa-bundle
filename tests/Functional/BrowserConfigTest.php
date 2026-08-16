<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\Dto\Theme;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\FaviconsBuilder;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use SpomkyLabs\PwaBundle\Service\SourceImageResolver;
use function sprintf;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class BrowserConfigTest extends KernelTestCase
{
    #[Test]
    public function eachTileNameDependsOnItsOwnConfigurationOnly(): void
    {
        // The tile hashes used to be chained, each one folding the previous, so inserting or removing a
        // tile renamed every file declared after it.

        // Given
        static::bootKernel();
        $compiler = $this->createCompilerWithTileColor();
        $asset = (string) file_get_contents(__DIR__ . '/../images/1920x1920.svg');
        $assetHash = hash('xxh128', $asset);

        // When
        $urls = [];
        foreach ($compiler->getFiles() as $url => $file) {
            $urls[] = $url;
        }

        // Then
        foreach ([[70, 70], [150, 150], [310, 310], [310, 150], [144, 144]] as [$width, $height]) {
            $configuration = Configuration::create($width, $height, 'png', null, null, null, false);
            $expected = sprintf(
                '/pwa/favicon-%dx%d-%s.png',
                $width,
                $height,
                hash('xxh128', $assetHash . $configuration)
            );
            static::assertContains(
                $expected,
                $urls,
                sprintf('the %dx%d tile name does not derive from the asset and its own configuration', $width, $height)
            );
        }
    }

    private function createCompilerWithTileColor(): FaviconsCompiler
    {
        $default = new Theme();
        $default->src = Asset::create('pwa/1920x1920.svg');

        $favicons = new Favicons();
        $favicons->enabled = true;
        $favicons->default = $default;
        $favicons->tileColor = '#ffffff';

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->method('denormalize')
            ->willReturn($favicons);

        return new FaviconsCompiler(
            static::getContainer()->get(ImageProcessorInterface::class),
            new FaviconsBuilder($denormalizer, []),
            static::getContainer()->get(SourceImageResolver::class),
            static::getContainer()->get(BasePathResolver::class),
            false
        );
    }
}
