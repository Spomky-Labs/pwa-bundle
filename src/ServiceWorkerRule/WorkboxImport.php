<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;

final class WorkboxImport implements ServiceWorkerRuleInterface, CanLogInterface
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
            $this->logger->debug('Workbox is disabled. The rule will not be applied.');
            return '';
        }
        $declaration = '';
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT


/**************************************************** WORKBOX IMPORT ****************************************************/
// The configuration is set to use Workbox
// The following code will import Workbox from CDN or public URL

DEBUG_COMMENT;
        }
        if ($this->workbox->useCDN === true) {
            if ($debug === true) {
                $declaration .= <<<DEBUG_COMMENT
// Import from CDN


DEBUG_COMMENT;
            }
            $declaration .= <<<IMPORT_CDN_STRATEGY
importScripts('https://storage.googleapis.com/workbox-cdn/releases/{$this->workbox->version}/workbox-sw.js');
importScripts('https://cdn.jsdelivr.net/npm/idb@8/build/umd.js');
IMPORT_CDN_STRATEGY;
        } else {
            $workboxPublicUrl = '/' . trim($this->workbox->workboxPublicUrl, '/');
            $idbPublicUrl = '/' . trim($this->workbox->indexDBPublicUrl, '/');
            if ($debug === true) {
                $declaration .= <<<DEBUG_COMMENT
// Import from public URL


DEBUG_COMMENT;
            }
            $declaration .= <<<IMPORT_CDN_STRATEGY
importScripts('{$workboxPublicUrl}/workbox-sw.js');
importScripts('{$idbPublicUrl}/umd.js');
workbox.setConfig({modulePathPrefix: '{$workboxPublicUrl}'});

IMPORT_CDN_STRATEGY;
        }
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT
/**************************************************** END WORKBOX IMPORT ****************************************************/




DEBUG_COMMENT;
        }
        $this->logger->debug('Workbox import rule added.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public static function getPriority(): int
    {
        return 1024;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
