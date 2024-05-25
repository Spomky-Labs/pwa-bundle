<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;

final class ClearCache implements ServiceWorkerRuleInterface, CanLogInterface
{
    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    public function __construct(
        ServiceWorker $serviceWorker,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->logger = new NullLogger();
    }

    public function process(bool $debug = false): string
    {
        if ($this->workbox->enabled === false) {
            $this->logger->debug('Workbox is disabled. The rule will not be applied.');
            return '';
        }
        if ($this->workbox->clearCache === false) {
            $this->logger->debug(
                'Workbox is enabled but the cache is not set to be cleared. The rule will not be applied.'
            );
            return '';
        }

        $declaration = '';
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT


/**************************************************** CACHE CLEAR ****************************************************/
// The configuration is set to clear the cache on each install event
// The following code will remove all the caches

DEBUG_COMMENT;
        }

        $declaration .= <<<CLEAR_CACHE
self.addEventListener("install", function (event) {
    event.waitUntil(caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.map(function (cacheName) {
                    return caches.delete(cacheName);
                })
            );
        })
    );
});

CLEAR_CACHE;

        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT
/**************************************************** END CACHE CLEAR ****************************************************/




DEBUG_COMMENT;
        }
        $this->logger->debug('Cache clear rule added.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
