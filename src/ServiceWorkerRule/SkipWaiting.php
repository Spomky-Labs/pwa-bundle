<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;

final readonly class SkipWaiting implements ServiceWorkerRuleInterface
{
    public function __construct(
        private ServiceWorker $serviceWorker
    ) {
    }

    public function process(bool $debug = false): string
    {
        if ($this->serviceWorker->skipWaiting === false) {
            return '';
        }

        $declaration = '';
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT


/**************************************************** SKIP WAITING ****************************************************/
// The configuration is set to skip waiting on each install event

DEBUG_COMMENT;
        }

        $declaration .= <<<SKIP_WAITING
self.addEventListener("install", function (event) {
  event.waitUntil(self.skipWaiting());
});
self.addEventListener("activate", function (event) {
  event.waitUntil(self.clients.claim());
});

SKIP_WAITING;
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT
/**************************************************** END SKIP WAITING ****************************************************/




DEBUG_COMMENT;
        }

        return $declaration;
    }
}
