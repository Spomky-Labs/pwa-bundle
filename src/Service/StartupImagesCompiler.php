<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use SpomkyLabs\PwaBundle\Dto\StartupImages;
use SpomkyLabs\PwaBundle\Dto\StartupImageTheme;
use SpomkyLabs\PwaBundle\StartupImage\ColorScheme;
use SpomkyLabs\PwaBundle\StartupImage\DeviceCatalog;
use SpomkyLabs\PwaBundle\StartupImage\IconStartupImageRenderer;
use SpomkyLabs\PwaBundle\StartupImage\StartupImageDefinition;
use SpomkyLabs\PwaBundle\StartupImage\StartupImageRendererInterface;
use SpomkyLabs\PwaBundle\StartupImage\TemplateStartupImageRenderer;
use function sprintf;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Generates the images iOS shows while the application is starting up.
 *
 * Every device is declared in both orientations, and once more per color scheme when a dark variant is
 * configured. What the image is made of is left to a renderer: the source image centered over the
 * background color, or a Twig template painted by a browser.
 */
final class StartupImagesCompiler implements FileCompilerInterface, CanLogInterface
{
    private LoggerInterface $logger;

    private readonly StartupImages $startupImages;

    public function __construct(
        StartupImagesBuilder $startupImagesBuilder,
        private readonly DeviceCatalog $deviceCatalog,
        private readonly IconStartupImageRenderer $iconRenderer,
        private readonly TemplateStartupImageRenderer $templateRenderer,
        private readonly BasePathResolver $basePathResolver,
        #[Autowire(param: 'kernel.debug')]
        private readonly bool $debug,
    ) {
        $this->startupImages = $startupImagesBuilder->create();
        $this->logger = new NullLogger();
    }

    /**
     * @return iterable<string, Data>
     */
    public function getFiles(): iterable
    {
        $this->logger->debug('Compiling startup images.', [
            'startupImages' => $this->startupImages,
        ]);
        if ($this->startupImages->enabled === false) {
            $this->logger->debug('Startup images are disabled.');
            return [];
        }

        $renderer = $this->getRenderer();
        foreach ($this->getThemes() as $colorSchemeValue => $theme) {
            $colorScheme = ColorScheme::from($colorSchemeValue);
            foreach ($this->deviceCatalog->allOrientations() as ['device' => $device, 'orientation' => $orientation]) {
                $definition = StartupImageDefinition::create($theme, $colorScheme, $device, $orientation);
                $url = sprintf(
                    '/pwa/start-image-%dx%d-%s.png',
                    $definition->width,
                    $definition->height,
                    $renderer->hash($definition)
                );

                yield $url => Data::create(
                    $url,
                    fn (): string => $renderer->render($definition),
                    $this->getHeaders(),
                    $this->getLink($url, $definition),
                );
            }
        }

        $this->logger->debug('Startup images created.');
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * No template means the historical image, which asks nothing of the application beyond the image
     * processor. A template is the opposite: it was configured on purpose, so a missing requirement is
     * reported rather than worked around.
     */
    private function getRenderer(): StartupImageRendererInterface
    {
        if ($this->startupImages->template === null) {
            return $this->iconRenderer;
        }
        $reason = $this->templateRenderer->getUnavailabilityReason();
        if ($reason !== null) {
            throw new RuntimeException($reason);
        }

        return $this->templateRenderer;
    }

    /**
     * @return array<string, StartupImageTheme>
     */
    private function getThemes(): array
    {
        $themes = [
            ColorScheme::LIGHT->value => $this->startupImages->default,
        ];
        if ($this->startupImages->dark !== null) {
            $themes[ColorScheme::DARK->value] = $this->startupImages->dark;
        }

        return $themes;
    }

    private function getLink(string $url, StartupImageDefinition $definition): string
    {
        return sprintf(
            '<link rel="apple-touch-startup-image" sizes="%dx%d" type="image/png" href="%s" media="%s">',
            $definition->width,
            $definition->height,
            $this->basePathResolver->prefix($url),
            $this->getMediaQuery($definition),
        );
    }

    /**
     * The device condition and the color scheme one are concatenated as-is: each of them is already a
     * chain of parenthesized media features, and wrapping the whole in an extra pair of parentheses would
     * turn it into a Media Queries Level 4 nested condition. Safari only parses that syntax from 16.4 on,
     * and a browser that fails to parse a media query discards it entirely, together with the startup
     * image it carries.
     */
    private function getMediaQuery(StartupImageDefinition $definition): string
    {
        if ($this->startupImages->dark === null) {
            return $definition->mediaQuery();
        }

        return $definition->mediaQuery() . ' and ' . $definition->colorScheme->mediaQuery();
    }

    /**
     * @return array<string, string|bool>
     */
    private function getHeaders(): array
    {
        if (! $this->debug) {
            return [];
        }

        return [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Content-Type' => 'image/png',
            'X-Pwa-Dev' => true,
        ];
    }
}
