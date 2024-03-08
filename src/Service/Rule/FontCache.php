<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class FontCache implements ServiceWorkerRule, HasCacheStrategies
{
    /**
     * @var array<string, mixed>
     */
    private array $jsonOptions;

    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        private AssetMapperInterface $assetMapper,
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
        if ($this->workbox->fontCache->enabled === false) {
            return $body;
        }
        $fonts = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            if (preg_match($this->workbox->fontCache->regex, $asset->sourcePath) === 1) {
                $fonts[] = $asset->publicPath;
            }
        }
        $fontUrls = $this->serializer->serialize($fonts, 'json', $this->jsonOptions);

        $declaration = <<<FONT_CACHE_RULE_STRATEGY
const fontCacheStrategy = new workbox.strategies.CacheFirst({
  cacheName: '{$this->workbox->fontCache->cacheName}',
  plugins: [
    new workbox.cacheableResponse.CacheableResponsePlugin({
      statuses: [0, 200],
    }),
    new workbox.expiration.ExpirationPlugin({
      maxAgeSeconds: {$this->workbox->fontCache->maxAge},
      maxEntries: {$this->workbox->fontCache->maxEntries},
    }),
  ],
});
workbox.routing.registerRoute(
  ({request}) => request.destination === 'font',
  fontCacheStrategy
);
self.addEventListener('install', event => {
  const done = {$fontUrls}.map(
    path =>
      fontCacheStrategy.handleAll({
        event,
        request: new Request(path),
      })[1]
  );

  event.waitUntil(Promise.all(done));
});
FONT_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    public function getCacheStrategies(): array
    {
        return [
            CacheStrategy::create(
                $this->workbox->fontCache->cacheName,
                CacheStrategy::STRATEGY_CACHE_FIRST,
                "'({request}) => request.destination === 'font'",
                $this->workbox->enabled && $this->workbox->fontCache->enabled,
                true,
                [
                    'maxEntries' => $this->workbox->fontCache->maxEntries,
                    'maxAge' => $this->workbox->fontCache->maxAge,
                ],
            ),
        ];
    }
}
