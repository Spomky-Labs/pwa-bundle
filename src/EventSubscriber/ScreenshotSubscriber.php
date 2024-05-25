<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Profiler\Profiler;

final class ScreenshotSubscriber implements EventSubscriberInterface, CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct(
        #[Autowire(service: 'profiler')]
        private readonly ?Profiler $profiler = null,
        #[Autowire(param: 'spomky_labs_pwa.screenshot_user_agent')]
        private readonly null|string $userAgent = null,
    ) {
        $this->logger = new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequest',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest() || $this->profiler === null) {
            return;
        }
        $this->logger->debug('Checking user agent.');

        $userAgent = $event->getRequest()
            ->headers->get('user-agent');
        if ($userAgent === null) {
            $this->logger->debug('No user agent found.');
            return;
        }
        $this->logger->debug('User agent found.', [
            'user_agent' => $userAgent,
        ]);
        $userAgentToFind = $this->userAgent ?? 'HeadlessChrome';
        if (! str_contains($userAgent, $userAgentToFind)) {
            $this->logger->debug('User agent does not match.', [
                'user_agent' => $userAgent,
            ]);
            return;
        }
        $this->logger->debug('User agent matches.', [
            'user_agent' => $userAgent,
        ]);

        $this->profiler->disable();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
