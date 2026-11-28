<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function assert;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\SpomkyLabsPwaBundle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ManifestBuilder
{
    private null|Manifest $manifest = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        private readonly array $config,
        #[Autowire(param: 'spomky_labs_pwa.translation_domain')]
        private readonly string $translationDomain = SpomkyLabsPwaBundle::DEFAULT_TRANSLATION_DOMAIN,
    ) {
    }

    public function create(): Manifest
    {
        if ($this->manifest === null) {
            $result = $this->denormalizer->denormalize($this->config, Manifest::class);
            assert($result instanceof Manifest);
            $result->setTranslationDomain($this->translationDomain);
            $this->manifest = $result;
        }

        return $this->manifest;
    }
}
