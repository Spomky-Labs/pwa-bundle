<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\EventListener;

use function in_array;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SpomkyLabs\PwaBundle\Dto\PreloadResource;
use SpomkyLabs\PwaBundle\Dto\ResourceHints;
use SpomkyLabs\PwaBundle\EventListener\ResourceHintsListener;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\ResourceHintsBuilder;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * @internal
 */
final class ResourceHintsListenerTest extends TestCase
{
    #[Test]
    public function itAddsLinkHeadersToHtmlRequests(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preconnect = ['https://api.example.com'];

        $listener = $this->createListener($hints, [
            'enabled' => true,
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
        static::assertContains('https://api.example.com', $hrefs);
    }

    #[Test]
    public function itDoesNothingWhenDisabled(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = false;

        $listener = $this->createListener($hints, [
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
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preconnect = ['https://api.example.com'];

        $listener = $this->createListener($hints, [
            'enabled' => true,
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
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preconnect = ['https://api.example.com'];

        $listener = $this->createListener($hints, [
            'enabled' => true,
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
    public function itAddsPreconnectLinks(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preconnect = ['https://api.example.com', 'https://cdn.example.com'];

        $listener = $this->createListener($hints, [
            'enabled' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        $links = $linkProvider->getLinks();

        $preconnectLinks = array_filter($links, fn ($link) => in_array(Link::REL_PRECONNECT, $link->getRels(), true));
        static::assertCount(2, $preconnectLinks);
    }

    #[Test]
    public function itAddsDnsPrefetchLinks(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->dnsPrefetch = ['https://analytics.example.com'];

        $listener = $this->createListener($hints, [
            'enabled' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        $links = $linkProvider->getLinks();

        $dnsPrefetchLinks = array_filter(
            $links,
            fn ($link) => in_array(Link::REL_DNS_PREFETCH, $link->getRels(), true)
        );
        static::assertCount(1, $dnsPrefetchLinks);
    }

    #[Test]
    public function itAddsPreloadLinks(): void
    {
        // Given
        $fontPreload = new PreloadResource();
        $fontPreload->href = '/fonts/custom.woff2';
        $fontPreload->as = 'font';
        $fontPreload->type = 'font/woff2';

        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preload = [$fontPreload];

        $listener = $this->createListener($hints, [
            'enabled' => true,
        ]);

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $event = $this->createRequestEvent($request);

        // When
        $listener($event);

        // Then
        $linkProvider = $request->attributes->get('_links');
        $links = $linkProvider->getLinks();

        $preloadLinks = array_filter($links, fn ($link) => in_array(Link::REL_PRELOAD, $link->getRels(), true));
        static::assertCount(1, $preloadLinks);

        $preloadLink = reset($preloadLinks);
        static::assertSame('/fonts/custom.woff2', $preloadLink->getHref());
        static::assertSame('font', $preloadLink->getAttributes()['as']);
    }

    #[Test]
    public function itPreservesExistingLinkProvider(): void
    {
        // Given
        $hints = new ResourceHints();
        $hints->enabled = true;
        $hints->autoPreconnect = false;
        $hints->preconnect = ['https://api.example.com'];

        $listener = $this->createListener($hints, [
            'enabled' => true,
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
        static::assertContains('https://api.example.com', $hrefs);
    }

    #[Test]
    public function itHasLowerPriorityThanEarlyHintsListener(): void
    {
        // ResourceHintsListener should run after EarlyHintsListener (priority 100)
        // so it has priority 99
        $reflection = new ReflectionClass(ResourceHintsListener::class);
        $attributes = $reflection->getAttributes();

        $listenerAttribute = null;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === AsEventListener::class) {
                $listenerAttribute = $attribute;
                break;
            }
        }

        static::assertNotNull($listenerAttribute);
        $args = $listenerAttribute->getArguments();
        static::assertSame(99, $args['priority']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createListener(ResourceHints $hints, array $config): ResourceHintsListener
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($hints);

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper
            ->method('getPublicPath')
            ->willReturnCallback(fn (string $path): string => '/' . $path);

        $resourceHintsBuilder = new ResourceHintsBuilder(
            $denormalizer,
            $assetMapper,
            $config,
            [],
            new BasePathResolver()
        );

        return new ResourceHintsListener($resourceHintsBuilder);
    }

    private function createRequestEvent(
        Request $request,
        int $requestType = HttpKernelInterface::MAIN_REQUEST
    ): RequestEvent {
        return new RequestEvent(static::createStub(HttpKernelInterface::class), $request, $requestType);
    }
}
