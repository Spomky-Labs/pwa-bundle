<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use SpomkyLabs\PwaBundle\Service\ResourceHintsBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\HttpHeaderSerializer;

final readonly class ResourceHintsRuntime
{
    public function __construct(
        private ResourceHintsBuilder $resourceHintsBuilder,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Render resource hints as HTML link tags.
     */
    public function render(): string
    {
        return $this->resourceHintsBuilder->generateHtml();
    }

    /**
     * Add resource hints to the current request for HTTP Link header.
     * This enables HTTP/2 Server Push when supported.
     */
    public function addToRequest(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $links = $this->resourceHintsBuilder->getLinks();
        if ($links === []) {
            return;
        }

        $linkProvider = $request->attributes->get('_links');
        if (! $linkProvider instanceof GenericLinkProvider) {
            $linkProvider = new GenericLinkProvider();
        }

        foreach ($links as $link) {
            $linkProvider = $linkProvider->withLink($link);
        }

        $request->attributes->set('_links', $linkProvider);
    }

    /**
     * Get the HTTP Link header value for the resource hints.
     */
    public function getHttpHeader(): string
    {
        $links = $this->resourceHintsBuilder->getLinks();
        if ($links === []) {
            return '';
        }

        $linkProvider = new GenericLinkProvider($links);
        $serializer = new HttpHeaderSerializer();

        return $serializer->serialize($linkProvider->getLinks());
    }
}
