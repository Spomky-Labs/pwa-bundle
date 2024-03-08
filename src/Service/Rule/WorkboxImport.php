<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use const PHP_EOL;

final readonly class WorkboxImport implements ServiceWorkerRule
{
    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker
    ) {
        $this->workbox = $serviceWorker->workbox;
    }

    public function process(string $body): string
    {
        if ($this->workbox->enabled === false) {
            return $body;
        }
        if ($this->workbox->useCDN === true) {
            $declaration = <<<IMPORT_CDN_STRATEGY
importScripts('https://storage.googleapis.com/workbox-cdn/releases/{$this->workbox->version}/workbox-sw.js');
IMPORT_CDN_STRATEGY;
        } else {
            $publicUrl = '/' . trim($this->workbox->workboxPublicUrl, '/');
            $declaration = <<<IMPORT_CDN_STRATEGY
importScripts('{$publicUrl}/workbox-sw.js');
workbox.setConfig({modulePathPrefix: '{$publicUrl}'});
IMPORT_CDN_STRATEGY;
        }

        return trim($declaration) . PHP_EOL . PHP_EOL . $body;
    }

    public static function getDefaultPriority(): int
    {
        return 1024;
    }
}
