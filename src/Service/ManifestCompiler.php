<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Event\NullEventDispatcher;
use SpomkyLabs\PwaBundle\Event\PostManifestCompileEvent;
use SpomkyLabs\PwaBundle\Event\PreManifestCompileEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\TranslatableNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use function assert;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class ManifestCompiler implements FileCompilerInterface
{
    private readonly EventDispatcherInterface $dispatcher;

    private readonly string $manifestPublicUrl;

    /**
     * @var array<string, mixed>
     */
    private readonly array $jsonOptions;

    /**
     * @param array<string> $locales
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly Manifest $manifest,
        #[Autowire('%spomky_labs_pwa.manifest.public_url%')]
        string $manifestPublicUrl,
        #[Autowire('%kernel.debug%')]
        bool $debug,
        null|EventDispatcherInterface $dispatcher,
        #[Autowire('%kernel.enabled_locales%')]
        private readonly array $locales,
    ) {
        $this->dispatcher = $dispatcher ?? new NullEventDispatcher();
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
        $options = [
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['useCredentials', 'locales'],
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ];
        if ($debug === true) {
            $options[JsonEncode::OPTIONS] |= JSON_PRETTY_PRINT;
        }
        $this->jsonOptions = $options;
    }

    /**
     * @return iterable<string, Data>
     */
    public function getFiles(): iterable
    {
        if ($this->manifest->enabled === false) {
            return [];
        }

        if ($this->locales === []) {
            yield $this->manifestPublicUrl => $this->compileManifest(null);
        }

        foreach ($this->locales as $locale) {
            yield str_replace('{locale}', $locale, $this->manifestPublicUrl) => $this->compileManifest($locale);
        }
    }

    private function compileManifest(null|string $locale): Data
    {
        $manifest = clone $this->manifest;
        $preEvent = new PreManifestCompileEvent($manifest);
        $preEvent = $this->dispatcher->dispatch($preEvent);
        assert($preEvent instanceof PreManifestCompileEvent);

        $options = $this->jsonOptions;
        $manifestPublicUrl = $this->manifestPublicUrl;
        if ($locale !== null) {
            $options[TranslatableNormalizer::NORMALIZATION_LOCALE_KEY] = $locale;
            $manifestPublicUrl = str_replace('{locale}', $locale, $this->manifestPublicUrl);
        }
        $data = $this->serializer->serialize($preEvent->manifest, 'json', $options);

        $postEvent = new PostManifestCompileEvent($manifest, $data);
        $postEvent = $this->dispatcher->dispatch($postEvent);
        assert($postEvent instanceof PostManifestCompileEvent);

        return Data::create(
            $manifestPublicUrl,
            $postEvent->data,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'application/manifest+json',
                'X-Manifest-Dev' => true,
                'Etag' => hash('xxh128', $data),
            ]
        );
    }
}
