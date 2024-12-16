<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\DataCollector;

use SpomkyLabs\PwaBundle\CachingStrategy\CacheStrategyInterface;
use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategiesInterface;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use SpomkyLabs\PwaBundle\Service\ManifestCompiler;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerCompiler;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Throwable;
use function count;
use function in_array;
use function is_array;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class PwaCollector extends DataCollector
{
    /**
     * @param iterable<HasCacheStrategiesInterface> $cachingServices
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        #[AutowireIterator('spomky_labs_pwa.cache_strategy')]
        private readonly iterable $cachingServices,
        private readonly Manifest $manifest,
        private readonly ServiceWorker $serviceWorker,
        private readonly Favicons $favicons,
        private readonly ManifestCompiler $manifestCompiler,
        private readonly ServiceWorkerCompiler $serviceWorkerCompiler,
        private readonly FaviconsCompiler $faviconsCompiler,
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $jsonOptions = [
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
        ];
        $this->data['cachingStrategies'] = [];
        foreach ($this->cachingServices as $cachingService) {
            foreach ($cachingService->getCacheStrategies() as $cacheStrategy) {
                $this->data['cachingStrategies'][] = $cacheStrategy;
            }
        }
        $swFiles = $this->serviceWorkerCompiler->getFiles();
        $swFiles = is_array($swFiles) ? $swFiles : iterator_to_array($swFiles);
        $this->data['serviceWorker'] = [
            'enabled' => $this->serviceWorker->enabled,
            'data' => $this->serviceWorker,
            'files' => $this->dataToFiles($swFiles),
        ];
        $manifestFiles = $this->manifestCompiler->getFiles();
        $manifestFiles = is_array($manifestFiles) ? $manifestFiles : iterator_to_array($manifestFiles);
        $this->data['manifest'] = [
            'enabled' => $this->serviceWorker->enabled,
            'data' => $this->manifest,
            'installable' => $this->isInstallable(),
            'output' => $this->serializer->serialize($this->manifest, 'json', $jsonOptions),
            'files' => $this->dataToFiles($manifestFiles),
        ];

        $faviconsFiles = $this->faviconsCompiler->getFiles();
        $faviconsFiles = is_array($faviconsFiles) ? $faviconsFiles : iterator_to_array($faviconsFiles);
        $this->data['favicons'] = [
            'enabled' => $this->favicons->enabled,
            'data' => $this->favicons,
            'files' => $this->dataToFiles($faviconsFiles),
        ];
    }

    /**
     * @return array<string, mixed>|Data
     */
    public function getData(): array|Data
    {
        return $this->data;
    }

    /**
     * @return array<CacheStrategyInterface>
     */
    public function getCachingStrategies(): array
    {
        return $this->data['cachingStrategies'] ?? [];
    }

    public function getManifest(): Manifest
    {
        return $this->data['manifest']['data'];
    }

    /**
     * @return array<string, Data>
     */
    public function getManifestFiles(): array
    {
        return $this->data['manifest']['files'];
    }

    public function getServiceWorker(): ServiceWorker
    {
        return $this->data['serviceWorker']['data'];
    }

    /**
     * @return array<string, Data>
     */
    public function getServiceWorkerFiles(): array
    {
        return $this->data['serviceWorker']['files'];
    }

    public function getWorkbox(): Workbox
    {
        return $this->data['serviceWorker']['data']->workbox;
    }

    public function getFavicons(): Favicons
    {
        return $this->data['favicons']['data'];
    }

    /**
     * @return array<string, Data>
     */
    public function getFaviconsFiles(): array
    {
        return $this->data['favicons']['files'];
    }

    public function getName(): string
    {
        return 'pwa';
    }

    /**
     * @param \SpomkyLabs\PwaBundle\Service\Data[] $data
     * @return array{url: string, html: string|null, headers: array<string, string|bool>}[]
     */
    private function dataToFiles(array $data): array
    {
        return array_map(
            static fn (\SpomkyLabs\PwaBundle\Service\Data $data): array => [
                'url' => $data->url,
                'headers' => $data->headers,
                'html' => $data->html,
            ],
            $data
        );
    }

    /**
     * @return array{status: bool, reasons: array<string, bool>}
     */
    private function isInstallable(): array
    {
        $reasons = [
            'The manifest must be enabled' => ! $this->manifest->enabled,
            'The manifest must have a short name or a name' => $this->manifest->shortName === null && $this->manifest->name === null,
            'The manifest must have a start URL' => $this->manifest->startUrl === null,
            'The manifest must have a display value set to "standalone", "fullscreen" or "minimal-ui' => ! in_array(
                $this->manifest->display,
                ['standalone', 'fullscreen', 'minimal-ui'],
                true
            ),
            'The manifest must have at least one icon' => count($this->manifest->icons) === 0,
            'The manifest must have the "prefer_related_applications" property set to a value other than "true"' => $this->manifest->preferRelatedApplications === true,
        ];

        return [
            'status' => count(array_filter($reasons, fn (bool $v): bool => $v)) === 0,
            'reasons' => $reasons,
        ];
    }
}
