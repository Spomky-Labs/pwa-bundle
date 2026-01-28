<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Dto\WorkboxConfig;
use SpomkyLabs\PwaBundle\EventListener\EarlyHintsListener;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * @internal
 */
final class EarlyHintsListenerTest extends TestCase
{
    #[Test]
    public function itAddsLinkHeadersToHtmlRequests(): void
    {
        // Given
        $manifest = new Manifest();
        $manifest->enabled = true;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        static::assertInstanceOf(GenericLinkProvider::class, $linkProvider);

        $links = $linkProvider->getLinks();
        static::assertNotEmpty($links);

        $hrefs = array_map(fn ($link) => $link->getHref(), $links);
        static::assertContains('/site.webmanifest', $hrefs);
    }

    #[Test]
    public function itDoesNothingWhenDisabled(): void
    {
        // Given
        $manifest = new Manifest();
        $manifest->enabled = true;

        $listener = $this->createListener($manifest, [
            'enabled' => false,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        static::assertNull($request->attributes->get('_links'));
    }

    #[Test]
    public function itDoesNothingForNonHtmlRequests(): void
    {
        // Given
        $manifest = new Manifest();
        $manifest->enabled = true;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        static::assertNull($request->attributes->get('_links'));
    }

    #[Test]
    public function itDoesNothingForSubRequests(): void
    {
        // Given
        $manifest = new Manifest();
        $manifest->enabled = true;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request, HttpKernelInterface::SUB_REQUEST);

        // When
        $listener($event);

        // Then
        static::assertNull($request->attributes->get('_links'));
    }

    #[Test]
    public function itPreloadsManifestWhenEnabled(): void
    {
        // Given
        $manifest = new Manifest();
        $manifest->enabled = true;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        $links = $linkProvider->getLinks();

        $manifestLinks = array_filter($links, fn ($link) => $link->getHref() === '/site.webmanifest');
        static::assertCount(1, $manifestLinks);

        $manifestLink = reset($manifestLinks);
        static::assertContains(Link::REL_PRELOAD, $manifestLink->getRels());
        static::assertSame('fetch', $manifestLink->getAttributes()['as']);
        static::assertSame('anonymous', $manifestLink->getAttributes()['crossorigin']);
    }

    #[Test]
    public function itDoesNotPreloadManifestWhenDisabled(): void
    {
        // Given
        $manifest = new Manifest();
        $manifest->enabled = true;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => false,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        static::assertNull($linkProvider);
    }

    #[Test]
    public function itPreloadsServiceWorkerWhenEnabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = false;

        $serviceWorker = new ServiceWorker();
        $serviceWorker->enabled = true;
        $serviceWorker->dest = '/sw.js';
        $serviceWorker->workbox = $workbox;

        $manifest = new Manifest();
        $manifest->enabled = true;
        $manifest->serviceWorker = $serviceWorker;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => false,
            'preload_serviceworker' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        $links = $linkProvider->getLinks();

        $swLinks = array_filter($links, fn ($link) => $link->getHref() === '/sw.js');
        static::assertCount(1, $swLinks);

        $swLink = reset($swLinks);
        static::assertContains(Link::REL_PRELOAD, $swLink->getRels());
        static::assertSame('script', $swLink->getAttributes()['as']);
    }

    #[Test]
    public function itPreconnectsToWorkboxCdnWhenEnabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->config = new WorkboxConfig();
        $workbox->config->useCDN = true;

        $serviceWorker = new ServiceWorker();
        $serviceWorker->enabled = true;
        $serviceWorker->dest = '/sw.js';
        $serviceWorker->workbox = $workbox;

        $manifest = new Manifest();
        $manifest->enabled = false;
        $manifest->serviceWorker = $serviceWorker;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => false,
            'preconnect_workbox_cdn' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        $links = $linkProvider->getLinks();

        $cdnLinks = array_filter($links, fn ($link) => $link->getHref() === 'https://storage.googleapis.com');
        static::assertCount(1, $cdnLinks);

        $cdnLink = reset($cdnLinks);
        static::assertContains(Link::REL_PRECONNECT, $cdnLink->getRels());
        static::assertTrue($cdnLink->getAttributes()['crossorigin']);
    }

    #[Test]
    public function itDoesNotPreconnectToWorkboxCdnWhenDisabled(): void
    {
        // Given
        $workbox = new Workbox();
        $workbox->enabled = true;
        $workbox->config = new WorkboxConfig();
        $workbox->config->useCDN = true;

        $serviceWorker = new ServiceWorker();
        $serviceWorker->enabled = true;
        $serviceWorker->dest = '/sw.js';
        $serviceWorker->workbox = $workbox;

        $manifest = new Manifest();
        $manifest->enabled = false;
        $manifest->serviceWorker = $serviceWorker;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => false,
            'preconnect_workbox_cdn' => false,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        static::assertNull($linkProvider);
    }

    #[Test]
    public function itPreservesExistingLinkProvider(): void
    {
        // Given
        $manifest = new Manifest();
        $manifest->enabled = true;

        $listener = $this->createListener($manifest, [
            'enabled' => true,
            'preload_manifest' => true,
        ]);

        $existingLink = new Link('preload', '/existing-resource.js');
        $existingProvider = (new GenericLinkProvider())->withLink($existingLink);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $request->attributes->set('_links', $existingProvider);
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        $links = $linkProvider->getLinks();

        $hrefs = array_map(fn ($link) => $link->getHref(), $links);
        static::assertContains('/existing-resource.js', $hrefs);
        static::assertContains('/site.webmanifest', $hrefs);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createListener(Manifest $manifest, array $config): EarlyHintsListener
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($manifest);

        $manifestBuilder = new ManifestBuilder($denormalizer, []);

        return new EarlyHintsListener($manifestBuilder, $config, 'site.webmanifest');
    }

    private function createRequestEvent(
        Request $request,
        int $requestType = HttpKernelInterface::MAIN_REQUEST
    ): RequestEvent {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, $requestType);
    }
}
