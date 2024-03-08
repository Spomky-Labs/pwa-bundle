<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Service\Rule\ServiceWorkerRule;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use function assert;
use function is_string;

final readonly class ServiceWorkerCompiler
{
    /**
     * @param iterable<ServiceWorkerRule> $serviceworkerRules
     */
    public function __construct(
        #[Autowire('%spomky_labs_pwa.sw.enabled%')]
        private bool $serviceWorkerEnabled,
        private ServiceWorker $serviceWorker,
        private AssetMapperInterface $assetMapper,
        #[TaggedIterator('spomky_labs_pwa.service_worker_rule', defaultPriorityMethod: 'getPriority')]
        private iterable $serviceworkerRules,
    ) {
    }

    public function compile(): ?string
    {
        if ($this->serviceWorkerEnabled === false) {
            return null;
        }
        $serviceWorker = $this->serviceWorker;

        if (! str_starts_with($serviceWorker->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($serviceWorker->src->src);
            assert($asset !== null, 'Unable to find service worker source asset');
            $body = $asset->content ?? file_get_contents($asset->sourcePath);
        } else {
            $body = file_get_contents($serviceWorker->src->src);
        }
        assert(is_string($body), 'Unable to find service worker source content');
        foreach ($this->serviceworkerRules as $rule) {
            $body = $rule->process($body);
        }

        return $body;
    }
}
