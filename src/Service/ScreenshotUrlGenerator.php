<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\ScreenshotConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ScreenshotUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Generate the URL from a configuration.
     * If the configuration uses a route, it will be generated using the Symfony router.
     * If the configuration uses a URL, it will be returned as-is.
     */
    public function generateUrl(ScreenshotConfiguration $config): string
    {
        // If it's a route, generate the URL
        if ($config->isRoute()) {
            return $this->urlGenerator->generate(
                $config->route,
                $config->routeParameters,
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        // Otherwise, return the URL as-is
        return $config->url;
    }
}
