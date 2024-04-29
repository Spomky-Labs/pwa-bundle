<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Favicons;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function assert;

final class FaviconsBuilder
{
    private null|Favicons $favicons = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        private readonly array $config,
    ) {
    }

    public function create(): Favicons
    {
        if ($this->favicons === null) {
            $result = $this->denormalizer->denormalize($this->config, Favicons::class);
            assert($result instanceof Favicons);
            $this->favicons = $result;
        }

        return $this->favicons;
    }
}
