<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\ServiceWorkerRuleInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use function assert;
use function is_string;

final readonly class ServiceWorkerCompiler
{
    /**
     * @param iterable<ServiceWorkerRuleInterface> $serviceworkerRules
     */
    public function __construct(
        private ServiceWorker $serviceWorker,
        private AssetMapperInterface $assetMapper,
        #[TaggedIterator('spomky_labs_pwa.service_worker_rule', defaultPriorityMethod: 'getPriority')]
        private iterable $serviceworkerRules,
        #[Autowire('%kernel.debug%')]
        public bool $debug,
    ) {
    }

    public function compile(): ?string
    {
        if ($this->serviceWorker->enabled === false) {
            return null;
        }
        $body = '';

        foreach ($this->serviceworkerRules as $rule) {
            $ruleBody = $rule->process($this->debug);
            if ($this->debug === false) {
                $ruleBody = trim($ruleBody);
            }
            $body .= $ruleBody;
        }

        return $body . $this->includeRootSW();
    }

    private function includeRootSW(): string
    {
        if ($this->serviceWorker->src->src === '') {
            return '';
        }
        if (! str_starts_with($this->serviceWorker->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($this->serviceWorker->src->src);
            assert($asset !== null, 'Unable to find service worker source asset');
            $body = $asset->content ?? file_get_contents($asset->sourcePath);
        } else {
            $body = file_get_contents($this->serviceWorker->src->src);
        }
        return is_string($body) ? $body : '';
    }
}
