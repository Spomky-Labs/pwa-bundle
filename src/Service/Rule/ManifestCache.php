<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CacheStrategy;
use SpomkyLabs\PwaBundle\Service\HasCacheStrategies;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use const PHP_EOL;

final readonly class ManifestCache implements ServiceWorkerRule, HasCacheStrategies
{
    private string $manifestPublicUrl;

    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        #[Autowire('%spomky_labs_pwa.manifest.public_url%')]
        string $manifestPublicUrl,
    ) {
        $this->workbox = $serviceWorker->workbox;
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
    }

    public function process(string $body): string
    {
        if ($this->workbox->enabled === false) {
            return $body;
        }
        if ($this->workbox->cacheManifest === false) {
            return $body;
        }

        $declaration = <<<IMAGE_CACHE_RULE_STRATEGY
workbox.routing.registerRoute(
  ({url}) => '{$this->manifestPublicUrl}' === url.pathname,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'manifest'
  })
);
IMAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    public function getCacheStrategies(): array
    {
        return [
            CacheStrategy::create(
                'manifest',
                CacheStrategy::STRATEGY_STALE_WHILE_REVALIDATE,
                sprintf("({url}) => '%s' === url.pathname", $this->manifestPublicUrl),
                $this->workbox->enabled && $this->workbox->cacheManifest,
                true
            ),
        ];
    }
}
