<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\ScriptSection;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

// Must run after WorkboxImport (1024) and WorkboxHelpers (1023) but before cache strategies
#[AsTaggedItem(priority: 1022)]
final class NavigationPreload implements ServiceWorkerRuleInterface, CanLogInterface
{
    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    public function __construct(ServiceWorkerBuilder $serviceWorkerBuilder)
    {
        $this->workbox = $serviceWorkerBuilder->create()
            ->workbox;
        $this->logger = new NullLogger();
    }

    public function process(bool $debug = false): string
    {
        if ($this->workbox->enabled === false) {
            $this->logger->debug('Workbox is disabled. Navigation preload rule will not be applied.');
            return '';
        }

        if ($this->workbox->navigationPreload === false) {
            $this->logger->debug('Navigation preload is disabled. The rule will not be applied.');
            return '';
        }

        $declaration = ScriptSection::create('NAVIGATION PRELOAD', $debug)
            ->comment(
                'Navigation Preload is enabled',
                'This speeds up navigation requests by making the network request in parallel with service worker boot-up',
                'See: https://developer.chrome.com/docs/workbox/modules/workbox-navigation-preload/'
            )
            ->code("workbox.navigationPreload.enable();\n")
            ->render();

        $this->logger->debug('Navigation preload rule applied.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
