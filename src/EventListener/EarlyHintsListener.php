<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\EventListener;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * Adds Link headers for PWA resources to enable Early Hints (HTTP 103).
 *
 * When using a server that supports Early Hints (FrankenPHP, Caddy, etc.),
 * these Link headers will be sent as 103 responses before the main response,
 * allowing browsers to start loading critical resources earlier.
 *
 * @see https://symfony.com/blog/new-in-symfony-6-3-early-hints
 */
#[AsEventListener(event: 'kernel.request', priority: 100)]
final readonly class EarlyHintsListener
{
    private Manifest $manifest;

    public function __construct(
        ManifestBuilder $manifestBuilder,
        #[Autowire(param: 'spomky_labs_pwa.early_hints.config')]
        private array $config,
        #[Autowire(param: 'spomky_labs_pwa.manifest.public_url')]
        private string $manifestPublicUrl,
    ) {
        $this->manifest = $manifestBuilder->create();
    }

    public function __invoke(RequestEvent $event): void
    {
        if (! ($this->config['enabled'] ?? false)) {
            return;
        }

        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Don't add hints for non-HTML requests
        $acceptHeader = $request->headers->get('Accept', '');
        if (! str_contains((string) $acceptHeader, 'text/html')) {
            return;
        }

        $links = $this->collectLinks();
        if ($links === []) {
            return;
        }

        // Get existing link provider or create a new one
        $linkProvider = $request->attributes->get('_links');
        if (! $linkProvider instanceof GenericLinkProvider) {
            $linkProvider = new GenericLinkProvider();
        }

        // Add PWA resource links
        foreach ($links as $link) {
            $linkProvider = $linkProvider->withLink($link);
        }

        $request->attributes->set('_links', $linkProvider);
    }

    /**
     * @return array<Link>
     */
    private function collectLinks(): array
    {
        $links = [];

        // Preload manifest if enabled
        if ($this->manifest->enabled && ($this->config['preload_manifest'] ?? true)) {
            $manifestUrl = '/' . trim($this->manifestPublicUrl, '/');
            $links[] = (new Link(Link::REL_PRELOAD, $manifestUrl))
                ->withAttribute('as', 'fetch')
                ->withAttribute('crossorigin', 'anonymous');
        }

        // Preload service worker if enabled
        if ($this->manifest->serviceWorker?->enabled && ($this->config['preload_serviceworker'] ?? false)) {
            $swUrl = $this->manifest->serviceWorker->dest;
            $links[] = (new Link(Link::REL_PRELOAD, $swUrl))
                ->withAttribute('as', 'script');
        }

        // Preconnect to Workbox CDN if using CDN
        if ($this->manifest->serviceWorker?->workbox->enabled
            && $this->manifest->serviceWorker->workbox->useCDN
            && ($this->config['preconnect_workbox_cdn'] ?? true)) {
            $links[] = (new Link(Link::REL_PRECONNECT, 'https://storage.googleapis.com'))
                ->withAttribute('crossorigin', true);
        }

        return $links;
    }
}
