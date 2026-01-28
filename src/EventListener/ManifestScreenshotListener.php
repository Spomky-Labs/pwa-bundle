<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\EventListener;

use function glob;
use function pathinfo;
use const PATHINFO_FILENAME;
use function preg_match;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\Screenshot;
use SpomkyLabs\PwaBundle\Event\PreManifestCompileEvent;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\ScreenshotAttributeCollector;
use function sprintf;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(event: PreManifestCompileEvent::class, priority: -100)]
final class ManifestScreenshotListener implements CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ScreenshotAttributeCollector $attributeCollector,
        private readonly RouterInterface $router,
        private readonly AssetMapperInterface $assetMapper,
    ) {
        $this->logger = new NullLogger();
    }

    public function __invoke(PreManifestCompileEvent $event): void
    {
        ['configurations' => $configurations] = $this->attributeCollector->collect($event->locale);

        $missingScreenshots = [];
        $wideScreenshots = [];
        $narrowScreenshots = [];

        foreach ($configurations as $config) {
            // Find all screenshot files matching this configuration
            $pattern = sprintf('%s%s-*x*.%s', $config->output, $config->filename, $config->format);
            $files = glob($pattern);
            if ($files === false) {
                $files = [];
            }

            if ($files === []) {
                $missingScreenshots[] = $pattern;
                continue;
            }

            foreach ($files as $filename) {
                // Extract dimensions from filename (e.g., "homepage-fr-1920x941.webp")
                $basename = pathinfo($filename, PATHINFO_FILENAME);
                if (preg_match('/-(\d+)x(\d+)$/', $basename, $matches) !== 1) {
                    continue;
                }

                $width = (int) $matches[1];
                $height = (int) $matches[2];

                $screenshot = new Screenshot();

                // Try to get asset from AssetMapper, fallback to filename
                $asset = $this->assetMapper->getAssetFromSourcePath($filename);
                $screenshot->src = new Asset(src: $asset === null ? $filename : $asset->logicalPath);

                $screenshot->width = $width;
                $screenshot->height = $height;
                $screenshot->formFactor = $width > $height ? 'wide' : 'narrow';
                $screenshot->locale = $config->locale;
                $screenshot->type = 'image/' . $config->format;

                if ($config->platform !== null) {
                    $screenshot->platform = $config->platform;
                }

                if ($config->label !== null) {
                    $screenshot->label = $config->label;
                }

                // Generate the reference URL from the route
                if ($config->route !== null) {
                    $screenshot->reference = $this->router->generate(
                        $config->route,
                        $config->routeParameters,
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                } elseif ($config->url !== null) {
                    $screenshot->reference = $config->url;
                }

                // Group by form factor
                if ($screenshot->formFactor === 'wide') {
                    $wideScreenshots[] = $screenshot;
                } else {
                    $narrowScreenshots[] = $screenshot;
                }
            }
        }

        // Add wide screenshots first, then narrow
        foreach ($wideScreenshots as $screenshot) {
            $event->manifest->screenshots[] = $screenshot;
        }
        foreach ($narrowScreenshots as $screenshot) {
            $event->manifest->screenshots[] = $screenshot;
        }

        if ($missingScreenshots !== []) {
            $this->logger->warning(
                'Missing screenshots. Run "bin/console pwa:create:screenshot --from-attributes" to generate them.',
                [
                    'missing' => $missingScreenshots,
                ]
            );
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
