<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\ScriptSection;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;

final class SkipWaiting implements ServiceWorkerRuleInterface, CanLogInterface
{
    private LoggerInterface $logger;

    private readonly ServiceWorker $serviceWorker;

    public function __construct(ServiceWorkerBuilder $serviceWorkerBuilder)
    {
        $this->serviceWorker = $serviceWorkerBuilder->create();
        $this->logger = new NullLogger();
    }

    public function process(bool $debug = false): string
    {
        if ($this->serviceWorker->skipWaiting === false) {
            $this->logger->debug('Skip waiting is disabled. The rule will not be applied.');
            return '';
        }

        $skipWaiting = $this->installDeclaration() . <<<'SKIP_WAITING'
self.addEventListener("activate", function (event) {
  event.waitUntil(self.clients.claim());
});

SKIP_WAITING;

        $declaration = ScriptSection::create('SKIP WAITING', $debug)
            ->comment('The configuration is set to skip waiting on each install event')
            ->code($skipWaiting)
            ->render();

        $this->logger->debug('Skip waiting rule applied.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * registerInstallTask() is declared by WorkboxHelpers, which emits nothing when Workbox is disabled. Without
     * Workbox, skipWaiting() is therefore called from a plain install listener.
     */
    private function installDeclaration(): string
    {
        if ($this->serviceWorker->workbox->enabled) {
            return "registerInstallTask(() => self.skipWaiting(), 5);\n";
        }

        return <<<'INSTALL'
self.addEventListener("install", function (event) {
  event.waitUntil(self.skipWaiting());
});

INSTALL;
    }
}
