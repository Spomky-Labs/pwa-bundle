<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use const PHP_EOL;
use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategiesInterface;
use function sprintf;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class AppendCacheStrategies implements ServiceWorkerRuleInterface
{
    /**
     * @param iterable<HasCacheStrategiesInterface> $cacheStrategies
     */
    public function __construct(
        #[AutowireIterator('spomky_labs_pwa.cache_strategy')]
        private iterable $cacheStrategies,
        #[Autowire(param: 'kernel.debug')]
        public bool $debug,
    ) {
    }

    public function process(bool $debug = false): string
    {
        $body = '';
        $cacheStrategyIndex = 0;
        foreach ($this->cacheStrategies as $cacheStrategy) {
            $strategyIndex = 0;
            foreach ($cacheStrategy->getCacheStrategies() as $strategy) {
                if ($strategy->isEnabled() === false) {
                    $strategyIndex++;
                    continue;
                }

                $body .= PHP_EOL . $strategy->render(
                    sprintf('cache_%d_%d', $cacheStrategyIndex, $strategyIndex),
                    $this->debug
                );
                $strategyIndex++;
            }
            $cacheStrategyIndex++;
        }

        return $body;
    }
}
