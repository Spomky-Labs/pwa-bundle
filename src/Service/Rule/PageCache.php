<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class PageCache implements WorkboxRule
{
    /**
     * @var array<string, mixed>
     */
    private array $jsonOptions;

    public function __construct(
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
        if ($workbox->pageCache->enabled === false) {
            return $body;
        }
        $routes = $this->serializer->serialize($workbox->pageCache->urls, 'json', $this->jsonOptions);

        $declaration = <<<PAGE_CACHE_RULE_STRATEGY
workbox.recipes.pageCache({
    cacheName: '{$workbox->pageCache->cacheName}',
    networkTimeoutSeconds: {$workbox->pageCache->networkTimeout},
    warmCache: {$routes}
});
PAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }
}
