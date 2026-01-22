<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\EventListener;

use SpomkyLabs\PwaBundle\Service\ResourceHintsBuilder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\WebLink\GenericLinkProvider;

/**
 * Adds Resource Hints Link headers early in the request cycle.
 *
 * This allows compatible servers (FrankenPHP, Caddy) to send HTTP 103 Early Hints
 * responses with preconnect, dns-prefetch, and preload hints before the main
 * response is ready.
 *
 * @see https://developer.chrome.com/docs/web-platform/early-hints
 */
#[AsEventListener(event: 'kernel.request', priority: 99)]
final readonly class ResourceHintsListener
{
    public function __construct(
        private ResourceHintsBuilder $resourceHintsBuilder,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (! $this->resourceHintsBuilder->isEnabled()) {
            return;
        }

        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only add hints for HTML requests
        $acceptHeader = $request->headers->get('Accept', '');
        if (! str_contains((string) $acceptHeader, 'text/html')) {
            return;
        }

        $links = $this->resourceHintsBuilder->getLinks();
        if ($links === []) {
            return;
        }

        // Get existing link provider or create a new one
        $linkProvider = $request->attributes->get('_links');
        if (! $linkProvider instanceof GenericLinkProvider) {
            $linkProvider = new GenericLinkProvider();
        }

        // Add resource hint links
        foreach ($links as $link) {
            $linkProvider = $linkProvider->withLink($link);
        }

        $request->attributes->set('_links', $linkProvider);
    }
}
