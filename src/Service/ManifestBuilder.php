<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function assert;

final class ManifestBuilder
{
    private null|Manifest $manifest = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        private readonly array $config,
    ) {
    }

    public function create(): Manifest
    {
        if ($this->manifest === null) {
            $result = $this->denormalizer->denormalize($this->config, Manifest::class);
            assert($result instanceof Manifest);
            $this->manifest = $result;
        }

        return $this->manifest;
    }
}
