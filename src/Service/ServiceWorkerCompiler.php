<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\ServiceWorkerRuleInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use function assert;
use function count;
use function in_array;
use function is_array;
use function is_string;

final readonly class ServiceWorkerCompiler implements FileCompilerInterface
{
    private string $serviceWorkerPublicUrl;

    private null|string $workboxPublicUrl;

    private null|string $workboxVersion;

    /**
     * @param iterable<ServiceWorkerRuleInterface> $serviceworkerRules
     */
    public function __construct(
        private ServiceWorker $serviceWorker,
        private AssetMapperInterface $assetMapper,
        #[TaggedIterator('spomky_labs_pwa.service_worker_rule', defaultPriorityMethod: 'getPriority')]
        private iterable $serviceworkerRules,
        #[Autowire('%kernel.debug%')]
        public bool $debug,
    ) {
        $serviceWorkerPublicUrl = $serviceWorker->dest;
        $this->serviceWorkerPublicUrl = '/' . trim($serviceWorkerPublicUrl, '/');
        if ($serviceWorker->workbox->enabled === true) {
            $this->workboxVersion = $serviceWorker->workbox->version;
            $workboxPublicUrl = $serviceWorker->workbox->workboxPublicUrl;
            $this->workboxPublicUrl = '/' . trim($workboxPublicUrl, '/');
        } else {
            $this->workboxVersion = null;
            $this->workboxPublicUrl = null;
        }
    }

    /**
     * @return iterable<string, Data>
     */
    public function getFiles(): iterable
    {
        yield $this->serviceWorkerPublicUrl => $this->compileSW();
        yield from $this->getWorkboxFiles();
    }

    private function compileSW(): Data
    {
        $body = '';

        foreach ($this->serviceworkerRules as $rule) {
            $ruleBody = $rule->process($this->debug);
            if ($this->debug === false) {
                $ruleBody = trim($ruleBody);
            }
            $body .= $ruleBody;
        }
        $body .= $this->includeRootSW();

        return Data::create(
            $this->serviceWorkerPublicUrl,
            $body,
            [
                'Content-Type' => 'application/javascript',
                'X-SW-Dev' => true,
                'Etag' => hash('xxh128', $body),
            ]
        );
    }

    private function includeRootSW(): string
    {
        if ($this->serviceWorker->src->src === '') {
            return '';
        }
        if (! str_starts_with($this->serviceWorker->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($this->serviceWorker->src->src);
            assert($asset !== null, 'Unable to find service worker source asset');
            $body = $asset->content ?? file_get_contents($asset->sourcePath);
        } else {
            $body = file_get_contents($this->serviceWorker->src->src);
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
            yield $publicUrl => $data;
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

        $body = file_get_contents($resourcePath);
        assert(is_string($body), 'Unable to load the file content.');

        return Data::create(
            $publicUrl,
            $body,
            [
                'Content-Type' => 'application/javascript',
                'X-SW-Dev' => true,
                'Etag' => hash('xxh128', $body),
            ]
        );
    }
}
