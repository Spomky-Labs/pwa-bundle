<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Subscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\Data;
use SpomkyLabs\PwaBundle\Service\FileCompilerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;

final class PwaDevServerSubscriber implements EventSubscriberInterface, CanLogInterface
{
    private LoggerInterface $logger;

    /**
     * @param iterable<FileCompilerInterface> $fileCompilers
     */
    public function __construct(
        #[AutowireIterator('spomky_labs_pwa.compiler')]
        private readonly iterable $fileCompilers,
        private readonly null|Profiler $profiler,
    ) {
        $this->logger = new NullLogger();
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        foreach ($this->fileCompilers as $fileCompiler) {
            foreach ($fileCompiler->getFiles() as $data) {
                if ($data->url !== $pathInfo) {
                    continue;
                }
                $this->logger->debug('PWA Dev Server file found.', [
                    'url' => $data->url,
                    'data' => $data,
                ]);
                $this->serveFile($event, $data);
                return;
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $headers = $event->getResponse()
            ->headers;
        if ($headers->has('X-Pwa-Dev')) {
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

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function serveFile(RequestEvent $event, Data $data): void
    {
        $this->profiler?->disable();
        $response = new Response($data->getData(), Response::HTTP_OK, $data->headers);
        $event->setResponse($response);
        $event->stopPropagation();
    }
}
