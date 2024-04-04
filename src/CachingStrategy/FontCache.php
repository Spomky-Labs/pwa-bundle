<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\WorkboxPlugin\CacheableResponsePlugin;
use SpomkyLabs\PwaBundle\WorkboxPlugin\ExpirationPlugin;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use function count;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class FontCache implements HasCacheStrategiesInterface
{
    private int $jsonOptions;

    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        private AssetMapperInterface $assetMapper,
        private SerializerInterface $serializer,
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
        $urls = json_decode($this->serializer->serialize($this->getFonts(), 'json', [
            JsonEncode::OPTIONS => $this->jsonOptions,
        ]), true);
        $maxEntries = count($urls) + ($this->workbox->fontCache->maxEntries ?? 60);

        $strategy = WorkboxCacheStrategy::create(
            $this->workbox->enabled && $this->workbox->fontCache->enabled,
            true,
            CacheStrategyInterface::STRATEGY_CACHE_FIRST,
            "({request}) => request.destination === 'font'"
        )
            ->withName($this->workbox->fontCache->cacheName ?? 'fonts')
            ->withMethod('GET')
            ->withPlugin(
                CacheableResponsePlugin::create(),
                ExpirationPlugin::create(
                    $maxEntries,
                    $this->workbox->fontCache->maxAgeInSeconds() ?? 60 * 60 * 24 * 365
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
    private function getFonts(): array
    {
        $fonts = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            if (preg_match($this->workbox->fontCache->regex, $asset->sourcePath) === 1) {
                $fonts[] = $asset->publicPath;
            }
        }
        return $fonts;
    }
}
