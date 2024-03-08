<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use const PHP_EOL;

final readonly class ManifestCache implements WorkboxRule
{
    private string $manifestPublicUrl;

    public function __construct(
        #[Autowire('%spomky_labs_pwa.manifest.public_url%')]
        string $manifestPublicUrl,
    ) {
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
    }

    public function process(Workbox $workbox, string $body): string
    {
        if ($workbox->cacheManifest === false) {
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
}
