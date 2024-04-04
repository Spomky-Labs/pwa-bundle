<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;

final readonly class WorkboxImport implements ServiceWorkerRuleInterface
{
    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker
    ) {
        $this->workbox = $serviceWorker->workbox;
    }

    public function process(bool $debug = false): string
    {
        if ($this->workbox->enabled === false) {
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
IMPORT_CDN_STRATEGY;
        } else {
            $publicUrl = '/' . trim($this->workbox->workboxPublicUrl, '/');
            if ($debug === true) {
                $declaration .= <<<DEBUG_COMMENT
// Import from public URL


DEBUG_COMMENT;
            }
            $declaration .= <<<IMPORT_CDN_STRATEGY
importScripts('{$publicUrl}/workbox-sw.js');
workbox.setConfig({modulePathPrefix: '{$publicUrl}'});

IMPORT_CDN_STRATEGY;
        }
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT
/**************************************************** END WORKBOX IMPORT ****************************************************/




DEBUG_COMMENT;
        }

        return $declaration;
    }

    public static function getPriority(): int
    {
        return 1024;
    }
}
