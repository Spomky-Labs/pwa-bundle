<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use SpomkyLabs\PwaBundle\Service\MatchCallbackHandler\MatchCallbackHandler;
use SpomkyLabs\PwaBundle\Service\Plugin\CachePlugin;
use SpomkyLabs\PwaBundle\Service\WorkboxCacheStrategy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class PageCaches implements HasCacheStrategies
{
    private int $jsonOptions;

    private Workbox $workbox;

    /**
     * @param iterable<MatchCallbackHandler> $matchCallbackHandlers
     */
    public function __construct(
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
        foreach ($this->workbox->pageCaches as $id => $pageCache) {
            $routes = $this->serializer->serialize($pageCache->urls, 'json', [
                JsonEncode::OPTIONS => $this->jsonOptions,
            ]);
            $url = json_decode($routes, true, 512, JSON_THROW_ON_ERROR);
            $cacheName = $pageCache->cacheName ?? sprintf('page-cache-%d', $id);

            $plugins = [
                CachePlugin::createCacheableResponsePlugin(
                    $pageCache->cacheableResponseStatuses,
                    $pageCache->cacheableResponseHeaders
                ),
            ];
            if ($pageCache->broadcast === true && $pageCache->strategy === CacheStrategy::STRATEGY_STALE_WHILE_REVALIDATE) {
                $plugins[] = CachePlugin::createBroadcastUpdatePlugin($pageCache->broadcastHeaders);
            }
            if ($pageCache->rangeRequests === true && $pageCache->strategy !== CacheStrategy::STRATEGY_NETWORK_ONLY) {
                $plugins[] = CachePlugin::createRangeRequestsPlugin();
            }
            if ($pageCache->maxEntries !== null || $pageCache->maxAgeInSeconds() !== null) {
                $plugins[] = CachePlugin::createExpirationPlugin($pageCache->maxEntries, $pageCache->maxAgeInSeconds());
            }

            $strategies[] =
                WorkboxCacheStrategy::create(
                    $cacheName,
                    $pageCache->strategy,
                    $this->prepareMatchCallback($pageCache->matchCallback),
                    $this->workbox->enabled,
                    true,
                    null,
                    $plugins,
                    $url,
                    [
                        'networkTimeoutSeconds' => $pageCache->networkTimeout,
                    ]
                );
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
}
