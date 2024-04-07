<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Url;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\MatchCallbackHandler\MatchCallbackHandlerInterface;
use SpomkyLabs\PwaBundle\WorkboxPlugin\BroadcastUpdatePlugin;
use SpomkyLabs\PwaBundle\WorkboxPlugin\CacheableResponsePlugin;
use SpomkyLabs\PwaBundle\WorkboxPlugin\ExpirationPlugin;
use SpomkyLabs\PwaBundle\WorkboxPlugin\RangeRequestsPlugin;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use function count;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class ResourceCaches implements HasCacheStrategiesInterface
{
    private int $jsonOptions;

    private Workbox $workbox;

    /**
     * @param iterable<MatchCallbackHandlerInterface> $matchCallbackHandlers
     */
    public function __construct(
        private PreloadUrlsGeneratorManager $preloadUrlsGeneratorManager,
        ServiceWorker $serviceWorker,
        private SerializerInterface $serializer,
        #[TaggedIterator('spomky_labs_pwa.match_callback_handler')]
        private iterable $matchCallbackHandlers,
        #[Autowire('%kernel.debug%')]
        bool $debug,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
        if ($debug === true) {
            $options |= JSON_PRETTY_PRINT;
        }
        $this->jsonOptions = $options;
    }

    public function getCacheStrategies(): array
    {
        $strategies = [];
        foreach ($this->workbox->resourceCaches as $id => $resourceCache) {
            $routes = $this->serializer->serialize($this->getUrls($resourceCache->urls), 'json', [
                JsonEncode::OPTIONS => $this->jsonOptions,
            ]);
            $urls = json_decode($routes, true, 512, JSON_THROW_ON_ERROR);
            $cacheName = $resourceCache->cacheName ?? sprintf('page-cache-%d', $id);

            $plugins = [
                CacheableResponsePlugin::create(
                    $resourceCache->cacheableResponseStatuses,
                    $resourceCache->cacheableResponseHeaders
                ),
            ];
            if ($resourceCache->broadcast === true && $resourceCache->strategy === CacheStrategyInterface::STRATEGY_STALE_WHILE_REVALIDATE) {
                $plugins[] = BroadcastUpdatePlugin::create($resourceCache->broadcastHeaders);
            }
            if ($resourceCache->rangeRequests === true && $resourceCache->strategy !== CacheStrategyInterface::STRATEGY_NETWORK_ONLY) {
                $plugins[] = RangeRequestsPlugin::create();
            }
            if ($resourceCache->maxEntries !== null || $resourceCache->maxAgeInSeconds() !== null) {
                $plugins[] = ExpirationPlugin::create($resourceCache->maxEntries, $resourceCache->maxAgeInSeconds());
            }

            $strategy = WorkboxCacheStrategy::create(
                $this->workbox->enabled,
                true,
                $resourceCache->strategy,
                $this->prepareMatchCallback($resourceCache->matchCallback)
            )
                ->withName($cacheName)
                ->withPlugin(...$plugins)
                ->withOptions([
                    'networkTimeoutSeconds' => $resourceCache->networkTimeout,
                ]);
            if (count($urls) > 0) {
                $strategy = $strategy->withPreloadUrl(...$urls);
            }

            $strategies[] = $strategy;
        }

        return $strategies;
    }

    private function prepareMatchCallback(string $matchCallback): string
    {
        foreach ($this->matchCallbackHandlers as $handler) {
            if ($handler->supports($matchCallback)) {
                return $handler->handle($matchCallback);
            }
        }

        return $matchCallback;
    }

    /**
     * @param array<Url> $urls
     * @return array<Url|string>
     */
    private function getUrls(array $urls): array
    {
        $result = [];
        foreach ($urls as $url) {
            if (str_starts_with($url->path, '@')) {
                $generator = $this->preloadUrlsGeneratorManager->get(mb_substr($url->path, 1));
                $list = $generator->generateUrls();
                foreach ($list as $item) {
                    $result[] = $item;
                }
            } else {
                $result[] = $url;
            }
        }

        return $result;
    }
}
