<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use function count;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SpomkyLabs\PwaBundle\Service\StartupImagesCompiler;
use SpomkyLabs\PwaBundle\Tests\DummyHtmlRenderer;
use function sprintf;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
final class StartupImageTemplateTest extends KernelTestCase
{
    use StartupImagesCompilerTrait;

    private const TEMPLATE = '@SpomkyLabsPwa/StartupImage/default.html.twig';

    #[Test]
    public function theDocumentIsPaintedAtTheSizeOfTheDevice(): void
    {
        // Given
        static::bootKernel();
        $htmlRenderer = new DummyHtmlRenderer();
        $compiler = $this->createStartupImagesCompiler(template: self::TEMPLATE, htmlRenderer: $htmlRenderer);

        // When
        foreach ($compiler->getFiles() as $file) {
            $file->getData();
        }

        // Then
        static::assertCount(42, $htmlRenderer->captures, '21 devices in two orientations');
        foreach ($htmlRenderer->captures as $capture) {
            static::assertStringContainsString(
                sprintf('width: %dpx', $capture['width']),
                $capture['html'],
                'the document does not declare the width it is painted at'
            );
            static::assertStringContainsString(sprintf('height: %dpx', $capture['height']), $capture['html']);
        }
    }

    #[Test]
    public function theTemplateIsHandedTheApplicationAndItsOwnContext(): void
    {
        // Given
        static::bootKernel();
        $htmlRenderer = new DummyHtmlRenderer();
        $compiler = $this->createStartupImagesCompiler(
            template: self::TEMPLATE,
            htmlRenderer: $htmlRenderer,
            context: [
                'subtitle' => 'Your daily companion',
            ]
        );

        // When
        foreach ($compiler->getFiles() as $file) {
            $file->getData();
            break;
        }

        // Then
        static::assertNotEmpty($htmlRenderer->captures);
        $html = $htmlRenderer->captures[0]['html'];
        static::assertStringContainsString('Your daily companion', $html, 'the free context is not exposed');
        static::assertStringContainsString('pwa.name', $html, 'the manifest name is not exposed');
        static::assertStringContainsString('data:image/svg+xml;base64,', $html, 'the source image is not inlined');
        static::assertStringContainsString('#ffffff', $html, 'the background color is not exposed');
    }

    #[Test]
    public function theLayoutFollowsTheOrientation(): void
    {
        // Given
        static::bootKernel();
        $htmlRenderer = new DummyHtmlRenderer();
        $compiler = $this->createStartupImagesCompiler(template: self::TEMPLATE, htmlRenderer: $htmlRenderer);

        // When
        foreach ($compiler->getFiles() as $file) {
            $file->getData();
        }

        // Then
        $portrait = array_values(array_filter(
            $htmlRenderer->captures,
            static fn (array $capture): bool => $capture['width'] < $capture['height']
        ));
        $landscape = array_values(array_filter(
            $htmlRenderer->captures,
            static fn (array $capture): bool => $capture['width'] > $capture['height']
        ));

        static::assertNotEmpty($portrait);
        static::assertNotEmpty($landscape);
        static::assertCount(count($portrait), $landscape, 'every device shall be declined in both orientations');
        // The shipped template gives the logo a different share of the screen in landscape, where the
        // vertical room is what runs out first.
        static::assertStringContainsString('--unit) * 34)', $portrait[0]['html']);
        static::assertStringContainsString('--unit) * 22)', $landscape[0]['html']);
    }

    #[Test]
    public function theFileNameFollowsWhatTheTemplateProduces(): void
    {
        // The images are served as immutable: a template edit that left the URL alone would be a stale
        // image in every cache between the application and the device.

        // Given
        static::bootKernel();

        // When
        $first = $this->collectUrls('Your daily companion');
        $again = $this->collectUrls('Your daily companion');
        $other = $this->collectUrls('Something else entirely');

        // Then
        static::assertSame($first, $again, 'the same template and context shall produce the same file names');
        static::assertSame([], array_intersect($first, $other), 'a context change shall rename every image');
    }

    #[Test]
    public function aMissingBrowserIsReportedRatherThanWorkedAround(): void
    {
        // Configuring a template is deliberate: falling back to the plain image would silently ship
        // something the application chose not to ask for.

        // Given
        static::bootKernel();
        $compiler = $this->createStartupImagesCompiler(template: self::TEMPLATE);

        // When
        $exception = $this->captureFailure($compiler);

        // Then
        static::assertInstanceOf(RuntimeException::class, $exception);
        static::assertStringContainsString(sprintf(
            'The startup images are described by the template "%s", but nothing can paint the document.',
            self::TEMPLATE
        ), $exception->getMessage());
    }

    #[Test]
    public function aMissingTwigIsReportedRatherThanWorkedAround(): void
    {
        // Given
        static::bootKernel();
        $compiler = $this->createStartupImagesCompiler(
            template: self::TEMPLATE,
            htmlRenderer: new DummyHtmlRenderer(),
            withTwig: false
        );

        // When
        $exception = $this->captureFailure($compiler);

        // Then
        static::assertInstanceOf(RuntimeException::class, $exception);
        static::assertStringContainsString(
            'Twig is not available. Install "symfony/twig-bundle".',
            $exception->getMessage()
        );
    }

    #[Test]
    public function noTemplateKeepsThePlainImage(): void
    {
        // Given
        static::bootKernel();
        $htmlRenderer = new DummyHtmlRenderer();
        $compiler = $this->createStartupImagesCompiler(htmlRenderer: $htmlRenderer);

        // When
        $files = iterator_to_array($compiler->getFiles());
        foreach ($files as $file) {
            $file->getData();
        }

        // Then
        static::assertCount(42, $files);
        static::assertSame([], $htmlRenderer->captures, 'no browser shall be needed without a template');
    }

    /**
     * The compilation is driven by hand rather than with expectException(), whose message assertion is
     * spelled differently from one PHPUnit major to the next.
     */
    private function captureFailure(StartupImagesCompiler $compiler): null|RuntimeException
    {
        try {
            iterator_to_array($compiler->getFiles());
        } catch (RuntimeException $exception) {
            return $exception;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function collectUrls(string $subtitle): array
    {
        $compiler = $this->createStartupImagesCompiler(
            template: self::TEMPLATE,
            htmlRenderer: new DummyHtmlRenderer(),
            context: [
                'subtitle' => $subtitle,
            ]
        );

        $urls = [];
        foreach ($compiler->getFiles() as $url => $file) {
            $urls[] = $url;
        }

        return $urls;
    }
}
