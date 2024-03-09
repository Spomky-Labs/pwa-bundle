<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

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

final readonly class PageCache implements ServiceWorkerRule, HasCacheStrategies
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
        if ($this->workbox->pageCache->enabled === false) {
            return $body;
        }
        $routes = $this->serializer->serialize($this->workbox->pageCache->urls, 'json', $this->jsonOptions);
        $strategy = match ($this->workbox->pageCache->strategy) {
            'staleWhileRevalidate' => 'StaleWhileRevalidate',
            default => 'NetworkFirst',
        };
        $broadcastHeaders = json_encode(
            $this->workbox->pageCache->broadcastHeaders === [] ? [
                'Content-Type',
                'ETag',
                'Last-Modified',
            ] : $this->workbox->pageCache->broadcastHeaders,
            JSON_THROW_ON_ERROR,
            512
        );
        $broadcastUpdate = ($strategy === 'StaleWhileRevalidate' && $this->workbox->pageCache->broadcast === true) ? sprintf(
            ',new workbox.broadcastUpdate.BroadcastUpdatePlugin({headersToCheck: %s})',
            $broadcastHeaders
        ) : '';

        $declaration = <<<PAGE_CACHE_RULE_STRATEGY
const pageCacheStrategy = new workbox.strategies.{$strategy}({
  networkTimeoutSeconds: {$this->workbox->pageCache->networkTimeout},
  cacheName: '{$this->workbox->pageCache->cacheName}',
  plugins: [new workbox.cacheableResponse.CacheableResponsePlugin({statuses: [0, 200]}){$broadcastUpdate}],
});
workbox.routing.registerRoute(
  ({request}) => request.mode === 'navigate',
  pageCacheStrategy
);
self.addEventListener('install', event => {
  const done = {$routes}.map(
    path =>
      pageCacheStrategy.handleAll({
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
        pageCacheStrategy.handleAll({
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

    public function getCacheStrategies(): array
    {
        $strategy = match ($this->workbox->pageCache->strategy) {
            'staleWhileRevalidate' => CacheStrategy::STRATEGY_STALE_WHILE_REVALIDATE,
            default => CacheStrategy::STRATEGY_NETWORK_FIRST,
        };
        $plugins = ['CacheableResponsePlugin'];
        if ($this->workbox->pageCache->broadcast === true && $strategy === CacheStrategy::STRATEGY_STALE_WHILE_REVALIDATE) {
            $plugins[] = 'BroadcastUpdatePlugin';
        }
        $routes = $this->serializer->serialize($this->workbox->pageCache->urls, 'json', $this->jsonOptions);
        $url = json_decode($routes, true, 512, JSON_THROW_ON_ERROR);
        return [
            CacheStrategy::create(
                $this->workbox->pageCache->cacheName,
                $strategy,
                "({request}) => request.mode === 'navigate'",
                $this->workbox->enabled && $this->workbox->pageCache->enabled,
                true,
                [
                    'maxTimeout' => $this->workbox->pageCache->networkTimeout,
                    'plugins' => $plugins,
                    'warmUrls' => $url,
                ]
            ),
        ];
    }
}
