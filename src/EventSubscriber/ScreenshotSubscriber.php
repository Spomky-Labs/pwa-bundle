<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Profiler\Profiler;

final readonly class ScreenshotSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'profiler')]
        private ?Profiler $profiler = null,
        #[Autowire(param: 'spomky_labs_pwa.screenshot_user_agent')]
        private null|string $userAgent = null,
    ) {
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

        $userAgent = $event->getRequest()
            ->headers->get('user-agent');
        if ($userAgent === null) {
            return;
        }
        $userAgentToFind = $this->userAgent ?? 'HeadlessChrome';
        if (! str_contains($userAgent, $userAgentToFind)) {
            return;
        }

        $this->profiler->disable();
    }
}
