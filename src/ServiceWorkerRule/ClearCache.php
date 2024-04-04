<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;

final readonly class ClearCache implements ServiceWorkerRuleInterface
{
    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
    ) {
        $this->workbox = $serviceWorker->workbox;
    }

    public function process(bool $debug = false): string
    {
        if ($this->workbox->enabled === false) {
            return '';
        }
        if ($this->workbox->clearCache === false) {
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

        return $declaration;
    }
}
