<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\WorkboxPlugin\ExpirationPlugin;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Path\PublicAssetsPathResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use function count;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class AssetCache implements HasCacheStrategiesInterface
{
    private int $jsonOptions;

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
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
        if ($debug === true) {
            $options |= JSON_PRETTY_PRINT;
        }
        $this->jsonOptions = $options;
    }

    public function getCacheStrategies(): array
    {
        $urls = json_decode($this->serializer->serialize($this->getAssets(), 'json', [
            JsonEncode::OPTIONS => $this->jsonOptions,
        ]), true);

        $strategy = WorkboxCacheStrategy::create(
            $this->workbox->enabled && $this->workbox->assetCache->enabled,
            true,
            CacheStrategyInterface::STRATEGY_CACHE_FIRST,
            sprintf("({url}) => url.pathname.startsWith('%s')", $this->assetPublicPrefix),
        )
            ->withName($this->workbox->assetCache->cacheName)
            ->withPlugin(
                ExpirationPlugin::create(
                    count($this->getAssets()) * 2,
                    $this->workbox->assetCache->maxAgeInSeconds() ?? 60 * 60 * 24 * 365,
                ),
            );

        if (count($urls) > 0) {
            $strategy = $strategy->withPreloadUrl(...$urls);
        }
        return [$strategy];
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
