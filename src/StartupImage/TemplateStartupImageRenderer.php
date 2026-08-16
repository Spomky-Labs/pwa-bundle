<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

use function assert;
use RuntimeException;
use function spl_object_id;
use SpomkyLabs\PwaBundle\Dto\StartupImages;
use SpomkyLabs\PwaBundle\Dto\StartupImageTheme;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use SpomkyLabs\PwaBundle\Service\SourceImage;
use SpomkyLabs\PwaBundle\Service\SourceImageResolver;
use SpomkyLabs\PwaBundle\Service\StartupImagesBuilder;
use function sprintf;
use Twig\Environment;

/**
 * Describes the image with a Twig template, and hands the document over to a renderer that paints it.
 *
 * The template is rendered by the application's own Twig environment: its globals, extensions and
 * filters are the ones it already knows.
 */
final class TemplateStartupImageRenderer implements StartupImageRendererInterface
{
    /**
     * The geometry the template is rendered with when its output is being fingerprinted rather than
     * shown. Both orientations are probed: a template that only lays out its subtitle in landscape would
     * otherwise keep its fingerprint when that branch is edited.
     */
    private const FINGERPRINT_PROBES = [[600, 800], [800, 600]];

    private readonly StartupImages $startupImages;

    /**
     * @var array<int, string>
     */
    private array $fingerprints = [];

    /**
     * @var array<int, SourceImage>
     */
    private array $sources = [];

    public function __construct(
        private readonly null|Environment $twig,
        private readonly null|HtmlRendererInterface $htmlRenderer,
        private readonly SourceImageResolver $sourceImageResolver,
        private readonly ManifestBuilder $manifestBuilder,
        StartupImagesBuilder $startupImagesBuilder,
    ) {
        $this->startupImages = $startupImagesBuilder->create();
    }

    /**
     * Why this renderer cannot work, ready to be thrown, or null when it is ready.
     *
     * Configuring a template is a deliberate act: what is missing is named, rather than silently falling
     * back to the plain image the application chose not to ask for.
     */
    public function getUnavailabilityReason(): null|string
    {
        $requirement = match (true) {
            $this->twig === null => 'Twig is not available. Install "symfony/twig-bundle".',
            $this->htmlRenderer === null => sprintf(
                'nothing can paint the document. Install "symfony/panther" together with a Chrome driver, or register a service implementing "%s".',
                HtmlRendererInterface::class
            ),
            default => null,
        };
        if ($requirement === null) {
            return null;
        }

        return sprintf(
            'The startup images are described by the template "%s", but %s',
            $this->startupImages->template ?? '',
            $requirement
        );
    }

    public function hash(StartupImageDefinition $definition): string
    {
        return hash('xxh128', sprintf(
            '%s%d%d%s%s',
            $this->getFingerprint($definition),
            $definition->width,
            $definition->height,
            $definition->orientation->value,
            $definition->colorScheme->value,
        ));
    }

    public function render(StartupImageDefinition $definition): string
    {
        if ($this->htmlRenderer === null) {
            $this->fail();
        }

        return $this->htmlRenderer->capture($this->renderHtml($definition), $definition->width, $definition->height);
    }

    public function renderHtml(StartupImageDefinition $definition): string
    {
        if ($this->twig === null) {
            $this->fail();
        }
        $template = $this->startupImages->template;
        assert($template !== null, 'The template renderer was used without a template being configured.');

        return $this->twig->render($template, $this->getContext($definition));
    }

    private function fail(): never
    {
        throw new RuntimeException($this->getUnavailabilityReason() ?? 'The startup images cannot be rendered.');
    }

    /**
     * A fingerprint of everything the rendering is made of, the geometry aside: the template and whatever
     * it pulls in, the variables it is handed, and the source image inlined in it.
     */
    private function getFingerprint(StartupImageDefinition $definition): string
    {
        $key = spl_object_id($definition->theme);
        if (isset($this->fingerprints[$key])) {
            return $this->fingerprints[$key];
        }

        $probes = '';
        foreach (self::FINGERPRINT_PROBES as [$width, $height]) {
            $probes .= $this->renderHtml(StartupImageDefinition::create(
                $definition->theme,
                $definition->colorScheme,
                Device::create('fingerprint', $width, $height, 1),
                $width <= $height ? Orientation::PORTRAIT : Orientation::LANDSCAPE,
            ));
        }

        return $this->fingerprints[$key] = hash('xxh128', $probes);
    }

    /**
     * @return array<string, mixed>
     */
    private function getContext(StartupImageDefinition $definition): array
    {
        $manifest = $this->manifestBuilder->create();
        $source = $this->getSource($definition->theme);
        $themeColor = $definition->colorScheme === ColorScheme::DARK
            ? $manifest->darkThemeColor ?? $manifest->themeColor
            : $manifest->themeColor;

        return [
            /* Geometry of the image, in device pixels. */
            'width' => $definition->width,
            'height' => $definition->height,
            'orientation' => $definition->orientation->value,
            /* Geometry of the device, in CSS pixels. */
            'device_width' => $definition->device->width,
            'device_height' => $definition->device->height,
            'pixel_ratio' => $definition->device->pixelRatio,
            'device_name' => $definition->device->name,
            /* Appearance. */
            'color_scheme' => $definition->colorScheme->value,
            'background_color' => $definition->theme->backgroundColor,
            'theme_color' => $themeColor,
            /* The application, as the manifest declares it. Values are the raw configured ones: run them
               through the "trans" filter with the "pwa" domain when they are translation keys. */
            'app_name' => $manifest->name,
            'short_name' => $manifest->shortName,
            'description' => $manifest->description,
            'lang' => $manifest->lang,
            'dir' => $manifest->dir,
            /* The source image, inlined: the browser paints the document from a temporary file, with no
               application URL to resolve an asset against. */
            'icon' => $source->getDataUri(),
            'icon_svg' => $source->isSvg() ? $source->content : null,
            /* Whatever the application chose to declare under "context". */
            'context' => $definition->theme->context,
        ];
    }

    private function getSource(StartupImageTheme $theme): SourceImage
    {
        return $this->sources[spl_object_id($theme)] ??= $this->sourceImageResolver->resolve(
            $theme->src,
            $theme->svgAttributes
        );
    }
}
