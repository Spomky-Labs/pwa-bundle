<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategies;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;

final readonly class AppendCacheStrategies implements ServiceWorkerRule
{
    private int $jsonOptions;

    /**
     * @param iterable<HasCacheStrategies> $cacheStrategies
     */
    public function __construct(
        #[TaggedIterator('spomky_labs_pwa.cache_strategy')]
        private iterable $cacheStrategies,
        #[Autowire('%kernel.debug%')]
        bool $debug,
    ) {
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
        if ($debug === true) {
            $options |= JSON_PRETTY_PRINT;
        }
        $this->jsonOptions = $options;
    }

    public function process(string $body): string
    {
        foreach ($this->cacheStrategies as $idCacheStrategy => $cacheStrategy) {
            foreach ($cacheStrategy->getCacheStrategies() as $idStrategy => $strategy) {
                if ($strategy->enabled === false) {
                    continue;
                }

                $body .= PHP_EOL . PHP_EOL . trim($strategy->render(
                    sprintf('cache_%d_%d', $idCacheStrategy, $idStrategy),
                    $this->jsonOptions
                ));
            }
        }

        return $body;
    }
}
