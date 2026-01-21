<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;

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

        $declaration = '';
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT


/**************************************************** NAVIGATION PRELOAD ****************************************************/
// Navigation Preload is enabled
// This speeds up navigation requests by making the network request in parallel with service worker boot-up
// See: https://developer.chrome.com/docs/workbox/modules/workbox-navigation-preload/

DEBUG_COMMENT;
        }

        $declaration .= <<<NAVIGATION_PRELOAD
workbox.navigationPreload.enable();

NAVIGATION_PRELOAD;

        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT
/**************************************************** END NAVIGATION PRELOAD ****************************************************/




DEBUG_COMMENT;
        }

        $this->logger->debug('Navigation preload rule applied.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public static function getPriority(): int
    {
        // Must run after WorkboxImport (1024) and WorkboxHelpers (1023)
        // but before cache strategies
        return 1022;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
