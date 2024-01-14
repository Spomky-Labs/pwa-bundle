<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class Builder
{
    private null|Manifest $manifest = null;

    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        private readonly array $config,
    ) {
    }

    public function createManifest(): Manifest
    {
        if ($this->manifest === null) {
            $this->manifest = $this->denormalizer->denormalize($this->config, Manifest::class);
        }

        return $this->manifest;
    }
}
