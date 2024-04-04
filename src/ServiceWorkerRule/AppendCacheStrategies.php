<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategiesInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use const PHP_EOL;

final readonly class AppendCacheStrategies implements ServiceWorkerRuleInterface
{
    /**
     * @param iterable<HasCacheStrategiesInterface> $cacheStrategies
     */
    public function __construct(
        #[TaggedIterator('spomky_labs_pwa.cache_strategy')]
        private iterable $cacheStrategies,
        #[Autowire('%kernel.debug%')]
        public bool $debug,
    ) {
    }

    public function process(bool $debug = false): string
    {
        $body = '';
        foreach ($this->cacheStrategies as $idCacheStrategy => $cacheStrategy) {
            foreach ($cacheStrategy->getCacheStrategies() as $idStrategy => $strategy) {
                if ($strategy->isEnabled() === false) {
                    continue;
                }

                $body .= PHP_EOL . $strategy->render(
                    sprintf('cache_%d_%d', $idCacheStrategy, $idStrategy),
                    $this->debug
                );
            }
        }

        return $body;
    }
}
