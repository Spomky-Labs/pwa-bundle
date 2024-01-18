<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class PwaDevServerSubscriber implements EventSubscriberInterface
{
    private string $manifestPublicUrl;

    private null|string $serviceWorkerPublicUrl;

    public function __construct(
        private ServiceWorkerBuilder $serviceWorkerBuilder,
        private SerializerInterface $serializer,
        private Manifest $manifest,
        #[Autowire('%spomky_labs_pwa.manifest_public_url%')]
        string $manifestPublicUrl,
        private null|Profiler $profiler,
    ) {
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
        $serviceWorkerPublicUrl = $manifest->serviceWorker?->dest;
        $this->serviceWorkerPublicUrl = $serviceWorkerPublicUrl === null ? null : '/' . trim(
            $serviceWorkerPublicUrl,
            '/'
        );
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        switch ($event->getRequest()->getPathInfo()) {
            case $this->manifestPublicUrl :
                $this->serveManifest($event);
                break;
            case $this->serviceWorkerPublicUrl :
                $this->serveServiceWorker($event);
                break;
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $headers = $event->getResponse()
->headers;
        if ($headers->has('X-Manifest-Dev') || $headers->has('X-SW-Dev')) {
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // priority higher than RouterListener
            KernelEvents::REQUEST => [['onKernelRequest', 35]],
            // Highest priority possible to bypass all other listeners
            KernelEvents::RESPONSE => [['onKernelResponse', 2048]],
        ];
    }

    private function serveManifest(RequestEvent $event): void
    {
        $this->profiler?->disable();
        $body = $this->serializer->serialize($this->manifest, 'json', [
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]);
        $response = new Response($body, Response::HTTP_OK, [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Content-Type' => 'application/manifest+json',
            'X-Manifest-Dev' => true,
            'Etag' => hash('xxh128', $body),
        ]);

        $event->setResponse($response);
        $event->stopPropagation();
    }

    private function serveServiceWorker(RequestEvent $event): void
    {
        $data = $this->serviceWorkerBuilder->build();
        if ($data === null) {
            return;
        }
        $this->profiler?->disable();

        $response = new Response($data, Response::HTTP_OK, [
            'Content-Type' => 'application/manifest+json',
            'X-SW-Dev' => true,
            'Etag' => hash('xxh128', $data),
        ]);

        $event->setResponse($response);
        $event->stopPropagation();
    }
}
