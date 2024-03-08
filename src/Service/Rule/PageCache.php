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

        $declaration = <<<PAGE_CACHE_RULE_STRATEGY
workbox.recipes.pageCache({
    cacheName: '{$this->workbox->pageCache->cacheName}',
    networkTimeoutSeconds: {$this->workbox->pageCache->networkTimeout},
    warmCache: {$routes}
});
PAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    public function getCacheStrategies(): array
    {
        return [
            CacheStrategy::create(
                $this->workbox->pageCache->cacheName,
                CacheStrategy::STRATEGY_STALE_WHILE_REVALIDATE,
                "'({request}) => request.mode === 'navigate'",
                $this->workbox->enabled && $this->workbox->pageCache->enabled,
                true,
                [
                    'maxTimeout' => $this->workbox->pageCache->networkTimeout,
                ]
            ),
        ];
    }
}
