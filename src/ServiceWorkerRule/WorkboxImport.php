<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use const JSON_UNESCAPED_SLASHES;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\ScriptSection;
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
        $section = ScriptSection::create('WORKBOX IMPORT', $debug)
            ->comment(
                'The configuration is set to use Workbox',
                'The following code will import Workbox from CDN or public URL'
            );

        if ($this->workbox->config->useCDN) {
            $section->comment('Import from CDN')
                ->code(<<<IMPORT_CDN_STRATEGY
importScripts('https://storage.googleapis.com/workbox-cdn/releases/{$this->workbox->config->version}/workbox-sw.js');

IMPORT_CDN_STRATEGY);
            // Only the deprecated helpers rely on self.idb. Once they are switched off,
            // shipping the library to every visitor buys nothing.
            if ($this->workbox->keepDeprecatedHelpers) {
                $section->code(<<<IMPORT_CDN_IDB
importScripts('https://cdn.jsdelivr.net/npm/idb@8/build/umd.js');

IMPORT_CDN_IDB);
            }
        } else {
            $workboxPublicUrl = $this->basePathResolver->prefix(
                '/' . trim($this->workbox->config->workboxPublicUrl, '/')
            );
            $idbPublicUrl = $this->basePathResolver->prefix('/' . trim($this->workbox->indexDBPublicUrl, '/'));
            $section->comment('Import from public URL')
                ->code(<<<IMPORT_PUBLIC_URL_STRATEGY
importScripts('{$workboxPublicUrl}/workbox-sw.js');

IMPORT_PUBLIC_URL_STRATEGY);
            if ($this->workbox->keepDeprecatedHelpers) {
                $section->code(<<<IMPORT_PUBLIC_URL_IDB
importScripts('{$idbPublicUrl}/umd.js');

IMPORT_PUBLIC_URL_IDB);
            }
            $section->code(<<<WORKBOX_MODULE_PATH
workbox.setConfig({modulePathPrefix: '{$workboxPublicUrl}'});

WORKBOX_MODULE_PATH);
        }

        // Add workbox configuration
        $configOptions = [];
        if (isset($this->workbox->config) && ! $this->workbox->config->debug) {
            $configOptions['debug'] = false;
        }

        if ($configOptions !== []) {
            $configJson = json_encode($configOptions, JSON_UNESCAPED_SLASHES);
            $section->comment('Additional Workbox configuration')
                ->code("workbox.setConfig({$configJson});\n");
        }

        $declaration = $section->render();
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
