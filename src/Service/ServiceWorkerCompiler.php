<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\ServiceWorkerRuleInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function assert;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

final class ServiceWorkerCompiler implements FileCompilerInterface, CanLogInterface
{
    private readonly string $serviceWorkerPublicUrl;

    private readonly null|string $workboxPublicUrl;

    private readonly null|string $workboxVersion;

    private LoggerInterface $logger;

    /**
     * @param iterable<ServiceWorkerRuleInterface> $serviceworkerRules
     */
    public function __construct(
        private readonly ServiceWorker $serviceWorker,
        private readonly AssetMapperInterface $assetMapper,
        #[AutowireIterator('spomky_labs_pwa.service_worker_rule', defaultPriorityMethod: 'getPriority')]
        private readonly iterable $serviceworkerRules,
        #[Autowire(param: 'kernel.debug')]
        public readonly bool $debug,
    ) {
        $serviceWorkerPublicUrl = $serviceWorker->dest;
        $this->serviceWorkerPublicUrl = '/' . mb_trim($serviceWorkerPublicUrl, '/');
        if ($serviceWorker->enabled === true && $serviceWorker->workbox->enabled === true) {
            $this->workboxVersion = $serviceWorker->workbox->version;
            $workboxPublicUrl = $serviceWorker->workbox->workboxPublicUrl;
            $this->workboxPublicUrl = '/' . mb_trim($workboxPublicUrl, '/');
        } else {
            $this->workboxVersion = null;
            $this->workboxPublicUrl = null;
        }
        $this->logger = new NullLogger();
    }

    /**
     * @return iterable<string, Data>
     */
    public function getFiles(): iterable
    {
        if ($this->serviceWorker->enabled === false) {
            yield from [];
            return;
        }
        $sw = $this->compileSW();
        yield $sw->url => $sw;
        yield from $this->getWorkboxFiles();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function compileSW(): Data
    {
        $this->logger->debug('Service Worker compilation started.');
        $callback = function (): string {
            $body = '';

            foreach ($this->serviceworkerRules as $rule) {
                $this->logger->debug('Processing service worker rule.', [
                    'rule' => $rule::class,
                ]);
                $ruleBody = $rule->process($this->debug);
                if ($this->debug === false) {
                    $ruleBody = mb_trim($ruleBody);
                }
                $body .= $ruleBody;
            }
            $body .= $this->includeRootSW();
            $this->logger->debug('Service Worker compilation completed.', [
                'body' => $body,
            ]);

            return $body;
        };

        return Data::create(
            $this->serviceWorkerPublicUrl,
            $callback,
            [
                'Content-Type' => 'application/javascript',
                'X-Pwa-Dev' => true,
            ]
        );
    }

    private function includeRootSW(): string
    {
        $source = $this->serviceWorker->src;
        if ($source === null) {
            return '';
        }
        if (! str_starts_with($source->src, '/')) {
            $asset = $this->assetMapper->getAsset($source->src);
            assert($asset !== null, 'Unable to find service worker source asset');
            $body = $asset->content ?? file_get_contents($asset->sourcePath);
        } else {
            assert(file_exists($source->src), 'Unable to find service worker source file');
            $body = file_get_contents($source->src);
        }
        return is_string($body) ? $body : '';
    }

    /**
     * @return iterable<string, Data>
     */
    private function getWorkboxFiles(): iterable
    {
        if ($this->serviceWorker->workbox->enabled === false) {
            return [];
        }
        if ($this->serviceWorker->workbox->useCDN === true) {
            return [];
        }
        $fileLocator = new FileLocator(__DIR__ . '/../Resources');
        $resourcePath = $fileLocator->locate(sprintf('workbox-v%s', $this->workboxVersion));

        $files = scandir($resourcePath);
        assert(is_array($files), 'Unable to list the files.');
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }
            if (str_contains($file, '.dev.') && $this->debug === false) {
                continue;
            }
            $path = sprintf('%s/%s', $resourcePath, $file);

            if (! is_file($path) || ! is_readable($path)) {
                continue;
            }
            $publicUrl = sprintf('%s/%s', $this->workboxPublicUrl, $file);
            $data = $this->getWorkboxFile($publicUrl);
            if ($data === null) {
                continue;
            }
            yield $data->url => $data;
        }
    }

    private function getWorkboxFile(string $publicUrl): null|Data
    {
        $asset = mb_substr($publicUrl, mb_strlen((string) $this->workboxPublicUrl));
        $fileLocator = new FileLocator(__DIR__ . '/../Resources');
        $resource = sprintf('workbox-v%s%s', $this->workboxVersion, $asset);
        $resourcePath = $fileLocator->locate($resource, null, false);
        if (is_array($resourcePath)) {
            if (count($resourcePath) === 1) {
                $resourcePath = $resourcePath[0];
            } else {
                return null;
            }
        }
        if (! is_string($resourcePath)) {
            return null;
        }
        if (! is_file($resourcePath) || ! is_readable($resourcePath)) {
            return null;
        }

        $callback = function () use ($resourcePath): string {
            $body = file_get_contents($resourcePath);
            assert(is_string($body), 'Unable to load the file content.');

            return $body;
        };

        return Data::create(
            $publicUrl,
            $callback,
            [
                'Content-Type' => 'application/javascript',
                'X-Pwa-Dev' => true,
            ]
        );
    }
}
