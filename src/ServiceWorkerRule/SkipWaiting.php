<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;

final class SkipWaiting implements ServiceWorkerRuleInterface, CanLogInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ServiceWorker $serviceWorker
    ) {
        $this->logger = new NullLogger();
    }

    public function process(bool $debug = false): string
    {
        if ($this->serviceWorker->skipWaiting === false) {
            $this->logger->debug('Skip waiting is disabled. The rule will not be applied.');
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
        $this->logger->debug('Skip waiting rule applied.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
