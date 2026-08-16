<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use const JSON_UNESCAPED_SLASHES;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 1024)]
final class WorkboxImport implements ServiceWorkerRuleInterface, CanLogInterface
{
    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    public function __construct(
        ServiceWorkerBuilder $serviceWorkerBuilder,
        private readonly BasePathResolver $basePathResolver,
    ) {
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
        if ($debug) {
            $declaration .= <<<DEBUG_COMMENT


/**************************************************** WORKBOX IMPORT ****************************************************/
// The configuration is set to use Workbox
// The following code will import Workbox from CDN or public URL

DEBUG_COMMENT;
        }
        if ($this->workbox->config->useCDN) {
            if ($debug) {
                $declaration .= <<<DEBUG_COMMENT
// Import from CDN


DEBUG_COMMENT;
            }
            $declaration .= <<<IMPORT_CDN_STRATEGY
importScripts('https://storage.googleapis.com/workbox-cdn/releases/{$this->workbox->config->version}/workbox-sw.js');
importScripts('https://cdn.jsdelivr.net/npm/idb@8/build/umd.js');
IMPORT_CDN_STRATEGY;
        } else {
            $workboxPublicUrl = $this->basePathResolver->prefix(
                '/' . trim($this->workbox->config->workboxPublicUrl, '/')
            );
            $idbPublicUrl = $this->basePathResolver->prefix('/' . trim($this->workbox->indexDBPublicUrl, '/'));
            if ($debug) {
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

        // Add workbox configuration
        $configOptions = [];
        if (isset($this->workbox->config) && ! $this->workbox->config->debug) {
            $configOptions['debug'] = false;
        }

        if ($configOptions !== []) {
            $configJson = json_encode($configOptions, JSON_UNESCAPED_SLASHES);
            if ($debug) {
                $declaration .= <<<DEBUG_COMMENT
// Additional Workbox configuration

DEBUG_COMMENT;
            }
            $declaration .= "workbox.setConfig({$configJson});\n";
        }

        if ($debug) {
            $declaration .= <<<DEBUG_COMMENT
/**************************************************** END WORKBOX IMPORT ****************************************************/




DEBUG_COMMENT;
        }
        $this->logger->debug('Workbox import rule added.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
