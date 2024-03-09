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
use function count;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class GoogleFontCache implements ServiceWorkerRule, HasCacheStrategies
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
        if ($this->workbox->googleFontCache->enabled === false) {
            return $body;
        }
        $options = [
            'cachePrefix' => $this->workbox->googleFontCache->cachePrefix,
            'maxAge' => $this->workbox->googleFontCache->maxAge,
            'maxEntries' => $this->workbox->googleFontCache->maxEntries,
        ];
        $options = array_filter($options, static fn (mixed $v): bool => ($v !== null && $v !== ''));
        $options = count($options) === 0 ? '' : $this->serializer->serialize($options, 'json', $this->jsonOptions);

        $declaration = <<<IMAGE_CACHE_RULE_STRATEGY
workbox.recipes.googleFontsCache({$options});
IMAGE_CACHE_RULE_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }

    public function getCacheStrategies(): array
    {
        return [
            CacheStrategy::create(
                'googleFontsCache',
                'workbox.recipes.googleFontsCache',
                'Google Fonts Cache',
                $this->workbox->enabled && $this->workbox->googleFontCache->enabled,
                $this->workbox->googleFontCache->enabled,
                [
                    'maxAge' => $this->workbox->googleFontCache->maxAge,
                    'maxEntries' => $this->workbox->googleFontCache->maxEntries,
                ]
            ),
        ];
    }
}
