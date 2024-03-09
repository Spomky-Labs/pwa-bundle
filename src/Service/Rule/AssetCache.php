<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Path\PublicAssetsPathResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use function count;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class AssetCache implements ServiceWorkerRule, HasCacheStrategies
{
    /**
     * @var array<string, mixed>
     */
    private array $jsonOptions;

    private string $assetPublicPrefix;

    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        #[Autowire(service: 'asset_mapper.public_assets_path_resolver')]
        PublicAssetsPathResolverInterface $publicAssetsPathResolver,
        private AssetMapperInterface $assetMapper,
        private SerializerInterface $serializer,
        #[Autowire('%kernel.debug%')]
        bool $debug,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->assetPublicPrefix = rtrim($publicAssetsPathResolver->resolvePublicPath(''), '/');
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
        if ($this->workbox->assetCache->enabled === false) {
            return $body;
        }
        $assets = $this->getAssets();
        $assetUrls = $this->serializer->serialize($assets, 'json', $this->jsonOptions);
        $assetUrlsLength = count($assets) * 2;

        $declaration = <<<ASSET_CACHE_RULE_STRATEGY
const assetCacheStrategy = new workbox.strategies.CacheFirst({
  cacheName: '{$this->workbox->assetCache->cacheName}',
  plugins: [
    new workbox.cacheableResponse.CacheableResponsePlugin({statuses: [0, 200]}),
    new workbox.expiration.ExpirationPlugin({
      maxEntries: {$assetUrlsLength},
      maxAgeSeconds: {$this->workbox->assetCache->maxAge},
    }),
  ],
});
workbox.routing.registerRoute(
  ({url}) => url.pathname.startsWith('{$this->assetPublicPrefix}'),
  assetCacheStrategy
);
self.addEventListener('install', event => {
  const done = {$assetUrls}.map(
    path =>
      assetCacheStrategy.handleAll({
        event,
        request: new Request(path),
      })[1]
  );

  event.waitUntil(Promise.all(done));
});
ASSET_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    public function getCacheStrategies(): array
    {
        $urls = json_decode($this->serializer->serialize($this->getAssets(), 'json', $this->jsonOptions), true);
        return [
            CacheStrategy::create(
                $this->workbox->assetCache->cacheName,
                CacheStrategy::STRATEGY_CACHE_FIRST,
                sprintf("'({url}) => url.pathname.startsWith('%s')'", $this->assetPublicPrefix),
                $this->workbox->enabled && $this->workbox->assetCache->enabled,
                true,
                [
                    'maxEntries' => count($this->getAssets()) * 2,
                    'maxAge' => $this->workbox->assetCache->maxAge,
                    'warmUrls' => $urls,
                ],
            ),
        ];
    }

    /**
     * @return array<string>
     */
    private function getAssets(): array
    {
        $assets = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            if (preg_match($this->workbox->assetCache->regex, $asset->sourcePath) === 1) {
                $assets[] = $asset->publicPath;
            }
        }
        return $assets;
    }
}
