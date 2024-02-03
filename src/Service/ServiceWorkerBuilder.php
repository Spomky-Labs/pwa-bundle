<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function assert;

final class ServiceWorkerBuilder
{
    private null|ServiceWorker $serviceWorker = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        private readonly array $config,
    ) {
    }

    public function create(): ServiceWorker
    {
        if ($this->serviceWorker === null) {
            $result = $this->denormalizer->denormalize($this->config, ServiceWorker::class);
            assert($result instanceof ServiceWorker);
            $this->serviceWorker = $result;
        }

        return $this->serviceWorker;
    }
}
