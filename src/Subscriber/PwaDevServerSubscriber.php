<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use SpomkyLabs\PwaBundle\Service\Data;
use SpomkyLabs\PwaBundle\Service\FileCompilerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use function in_array;

final readonly class PwaDevServerSubscriber implements EventSubscriberInterface
{
    /**
     * @param iterable<FileCompilerInterface> $fileCompilers
     */
    public function __construct(
        #[TaggedIterator('spomky_labs_pwa.compiler')]
        private iterable $fileCompilers,
        private null|Profiler $profiler,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        foreach ($this->fileCompilers as $fileCompiler) {
            if (in_array($pathInfo, $fileCompiler->supportedPublicUrls(), true)) {
                $data = $fileCompiler->get($pathInfo);
                assert($data !== null);
                $this->serveFile($event, $data);
            }
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

    private function serveFile(RequestEvent $event, Data $data): void
    {
        $this->profiler?->disable();
        $response = new Response($data->data, Response::HTTP_OK, $data->headers);
        $event->setResponse($response);
        $event->stopPropagation();
    }
}
