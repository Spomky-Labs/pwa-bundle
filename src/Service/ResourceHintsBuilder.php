<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use const ENT_HTML5;
use const ENT_QUOTES;
use SpomkyLabs\PwaBundle\Dto\PreloadResource;
use SpomkyLabs\PwaBundle\Dto\ResourceHints;
use function sprintf;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\WebLink\Link;

final class ResourceHintsBuilder
{
    private ?ResourceHints $resourceHints = null;

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $workboxConfig
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        #[Autowire(param: 'spomky_labs_pwa.resource_hints.config')]
        private readonly array $config,
        #[Autowire(param: 'spomky_labs_pwa.sw.config')]
        private readonly array $workboxConfig,
    ) {
    }

    public function create(): ResourceHints
    {
        if ($this->resourceHints === null) {
            $this->resourceHints = $this->denormalizer->denormalize($this->config, ResourceHints::class, 'json');
        }

        return $this->resourceHints;
    }

    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Get all Link objects for resource hints.
     *
     * @return array<Link>
     */
    public function getLinks(): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $hints = $this->create();
        $links = [];

        // Auto-detect preconnect origins
        $preconnectOrigins = $hints->preconnect;
        if ($hints->autoPreconnect) {
            $preconnectOrigins = array_merge($preconnectOrigins, $this->detectAutoPreconnectOrigins());
        }
        $preconnectOrigins = array_unique($preconnectOrigins);

        // Create preconnect links
        foreach ($preconnectOrigins as $origin) {
            $links[] = (new Link(Link::REL_PRECONNECT, rtrim($origin, '/')))
                ->withAttribute('crossorigin', true);
        }

        // Create dns-prefetch links
        foreach ($hints->dnsPrefetch as $origin) {
            $links[] = new Link(Link::REL_DNS_PREFETCH, rtrim($origin, '/'));
        }

        // Create preload links
        foreach ($hints->preload as $resource) {
            $links[] = $this->createPreloadLink($resource);
        }

        return $links;
    }

    /**
     * Generate the HTML for resource hints.
     */
    public function generateHtml(): string
    {
        $links = $this->getLinks();

        if ($links === []) {
            return '';
        }

        $output = '';
        foreach ($links as $link) {
            $output .= $this->renderLinkAsHtml($link);
        }

        return $output;
    }

    /**
     * Detect origins that should be preconnected based on bundle configuration.
     *
     * @return array<string>
     */
    private function detectAutoPreconnectOrigins(): array
    {
        $origins = [];

        // Check if Workbox CDN is used
        $workbox = $this->workboxConfig['workbox'] ?? [];
        if (($workbox['enabled'] ?? false) && ($workbox['use_cdn'] ?? false)) {
            $origins[] = 'https://storage.googleapis.com';
            $origins[] = 'https://cdn.jsdelivr.net';
        }

        // Check if Google Fonts cache is enabled
        $googleFonts = $workbox['google_fonts'] ?? [];
        if ($googleFonts['enabled'] ?? false) {
            $origins[] = 'https://fonts.googleapis.com';
            $origins[] = 'https://fonts.gstatic.com';
        }

        return $origins;
    }

    private function createPreloadLink(PreloadResource $resource): Link
    {
        $link = (new Link(Link::REL_PRELOAD, $resource->href))
            ->withAttribute('as', $resource->as);

        if ($resource->type !== null) {
            $link = $link->withAttribute('type', $resource->type);
        }

        if ($resource->crossorigin !== null) {
            $link = $link->withAttribute('crossorigin', $resource->crossorigin);
        } elseif ($resource->as === 'font') {
            // Fonts always require crossorigin
            $link = $link->withAttribute('crossorigin', 'anonymous');
        }

        if ($resource->fetchPriority !== null) {
            $link = $link->withAttribute('fetchpriority', $resource->fetchPriority);
        }

        if ($resource->media !== null) {
            $link = $link->withAttribute('media', $resource->media);
        }

        return $link;
    }

    private function renderLinkAsHtml(Link $link): string
    {
        $href = $link->getHref();
        $rels = $link->getRels();
        $attributes = $link->getAttributes();

        $rel = implode(' ', $rels);
        $attrString = sprintf(
            ' rel="%s" href="%s"',
            htmlspecialchars($rel, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );

        foreach ($attributes as $name => $value) {
            if ($value === true) {
                $attrString .= sprintf(' %s', $name);
            } elseif ($value !== false && $value !== null) {
                $attrString .= sprintf(
                    ' %s="%s"',
                    $name,
                    htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                );
            }
        }

        return sprintf("\n<link%s>", $attrString);
    }
}
