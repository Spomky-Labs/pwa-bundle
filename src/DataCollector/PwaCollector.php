<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\DataCollector;

use SpomkyLabs\PwaBundle\CachingStrategy\CacheStrategyInterface;
use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategiesInterface;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use function count;
use function in_array;
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
        #[TaggedIterator('spomky_labs_pwa.cache_strategy')]
        private readonly iterable $cachingServices,
        private readonly Manifest $manifest,
        private readonly ServiceWorker $serviceWorker,
        #[Autowire(param: 'spomky_labs_pwa.manifest.enabled')]
        private readonly bool $manifestEnabled,
    ) {
    }

    public function collect(Request $request, Response $response, Throwable $exception = null): void
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
        $this->data['serviceWorker'] = $this->serviceWorker;
        $this->data['manifest'] = [
            'enabled' => $this->manifestEnabled,
            'data' => $this->manifest,
            'installable' => $this->isInstallable(),
            'output' => $this->serializer->serialize($this->manifest, 'json', $jsonOptions),
        ];

        dump($this->data);
    }

    public function getData(): array
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

    public function getWorkbox(): Workbox
    {
        return $this->data['serviceWorker']->workbox;
    }

    public function getName(): string
    {
        return 'pwa';
    }

    /**
     * @return array{status: bool, reasons: string[]}
     */
    private function isInstallable(): array
    {
        $reasons = [
            'The manifest must be enabled' => ! $this->manifestEnabled,
            'The manifest must have a short name or a name' => $this->manifest->shortName === null && $this->manifest->name === null,
            'The manifest must have a start URL' => $this->manifest->startUrl === null,
            'The manifest must have a display value set to "standalone", "fullscreen" or "minimal-ui' => ! in_array(
                $this->manifest->display,
                ['standalone', 'fullscreen', 'minimal-ui'],
                true
            ),
            'The manifest must have at least one icon' => $this->manifest->icons === null || count(
                $this->manifest->icons
            ) === 0,
            'The manifest must have the "prefer_related_applications" property set to a value other than "true"' => $this->manifest->preferRelatedApplications === true,
        ];

        return [
            'status' => count(array_filter($reasons)) === 0,
            'reasons' => $reasons,
        ];
    }
}
