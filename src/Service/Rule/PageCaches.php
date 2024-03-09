<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\PageCache;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class PageCaches implements ServiceWorkerRule, HasCacheStrategies
{
    /**
     * @var array<string, mixed>
     */
    private array $jsonOptions;

    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        private SerializerInterface $serializer,
        #[Autowire('%kernel.debug%')]
        bool $debug,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $options = [
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ];
        if ($debug === true) {
            $options[JsonEncode::OPTIONS] |= JSON_PRETTY_PRINT;
        }
        $this->jsonOptions = $options;
    }

    public function process(string $body): string
    {
        if ($this->workbox->enabled === false) {
            return $body;
        }

        foreach (array_values($this->workbox->pageCaches) as $id => $pageCache) {
            $body = $this->processPageCache($id, $pageCache, $body);
        }

        return $body;
    }

    public function getCacheStrategies(): array
    {
        $strategies = [];
        foreach ($this->workbox->pageCaches as $pageCache) {
            $strategy = match ($pageCache->strategy) {
                'staleWhileRevalidate' => CacheStrategy::STRATEGY_STALE_WHILE_REVALIDATE,
                default => CacheStrategy::STRATEGY_NETWORK_FIRST,
            };
            $plugins = ['CacheableResponsePlugin'];
            if ($pageCache->broadcast === true && $strategy === CacheStrategy::STRATEGY_STALE_WHILE_REVALIDATE) {
                $plugins[] = 'BroadcastUpdatePlugin';
            }
            $routes = $this->serializer->serialize($pageCache->urls, 'json', $this->jsonOptions);
            $url = json_decode($routes, true, 512, JSON_THROW_ON_ERROR);
            $strategies[] =
                CacheStrategy::create(
                    $pageCache->cacheName,
                    $strategy,
                    $pageCache->regex,
                    $this->workbox->enabled,
                    true,
                    [
                        'maxTimeout' => $pageCache->networkTimeout,
                        'plugins' => $plugins,
                        'warmUrls' => $url,
                    ]
                );
        }

        return $strategies;
    }

    private function processPageCache(int $id, PageCache $pageCache, string $body): string
    {
        $routes = $this->serializer->serialize($pageCache->urls, 'json', $this->jsonOptions);
        $strategy = match ($pageCache->strategy) {
            'staleWhileRevalidate' => 'StaleWhileRevalidate',
            default => 'NetworkFirst',
        };
        $broadcastHeaders = json_encode(
            $pageCache->broadcastHeaders === [] ? [
                'Content-Type',
                'ETag',
                'Last-Modified',
            ] : $pageCache->broadcastHeaders,
            JSON_THROW_ON_ERROR,
            512
        );
        $broadcastUpdate = ($strategy === 'StaleWhileRevalidate' && $pageCache->broadcast === true) ? sprintf(
            ',new workbox.broadcastUpdate.BroadcastUpdatePlugin({headersToCheck: %s})',
            $broadcastHeaders
        ) : '';

        $declaration = <<<PAGE_CACHE_RULE_STRATEGY
const pageCache{$id}Strategy = new workbox.strategies.{$strategy}({
  networkTimeoutSeconds: {$pageCache->networkTimeout},
  cacheName: '{$pageCache->cacheName}',
  plugins: [new workbox.cacheableResponse.CacheableResponsePlugin({statuses: [0, 200]}){$broadcastUpdate}],
});
workbox.routing.registerRoute(
  new RegExp('{$pageCache->regex}'),
  pageCache{$id}Strategy
);
self.addEventListener('install', event => {
  const done = {$routes}.map(
    path =>
      pageCache{$id}Strategy.handleAll({
        event,
        request: new Request(path),
      })[1]
  );
  event.waitUntil(Promise.all(done));
});
fetchAsync = async (url) => {
  await fetch(url);
}
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'PREFETCH') {
    const urls = event.data.payload.urls || [];
    const done = urls.map(
      path =>
        pageCache{$id}Strategy.handleAll({
          event,
          request: new Request(path),
        })[1]
      );
      event.waitUntil(Promise.all(done));
  }
});
PAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }
}
