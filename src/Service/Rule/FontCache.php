<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\Workbox;
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

final readonly class FontCache implements WorkboxRule
{
    /**
     * @var array<string, mixed>
     */
    private array $jsonOptions;

    public function __construct(
        private AssetMapperInterface $assetMapper,
        private SerializerInterface $serializer,
        #[Autowire('%kernel.debug%')]
        bool $debug,
    ) {
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

    public function process(Workbox $workbox, string $body): string
    {
        if ($workbox->fontCache->enabled === false) {
            return $body;
        }
        $fonts = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            if (preg_match($workbox->fontCache->regex, $asset->sourcePath) === 1) {
                $fonts[] = $asset->publicPath;
            }
        }
        $fontUrls = $this->serializer->serialize($fonts, 'json', $this->jsonOptions);

        $declaration = <<<FONT_CACHE_RULE_STRATEGY
const fontCacheStrategy = new workbox.strategies.CacheFirst({
  cacheName: '{$workbox->fontCache->cacheName}',
  plugins: [
    new workbox.cacheableResponse.CacheableResponsePlugin({
      statuses: [0, 200],
    }),
    new workbox.expiration.ExpirationPlugin({
      maxAgeSeconds: {$workbox->fontCache->maxAge},
      maxEntries: {$workbox->fontCache->maxEntries},
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
}
