<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

final class ManifestCompiler implements FileCompilerInterface, CanLogInterface
{
    private readonly EventDispatcherInterface $dispatcher;

    private readonly string $manifestPublicUrl;

    /**
     * @var array<string, mixed>
     */
    private readonly array $jsonOptions;

    private LoggerInterface $logger;

    /**
     * @param array<string> $locales
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly Manifest $manifest,
        #[Autowire(param: 'spomky_labs_pwa.manifest.public_url')]
        string $manifestPublicUrl,
        #[Autowire(param: 'kernel.debug')]
        bool $debug,
        null|EventDispatcherInterface $dispatcher,
        #[Autowire(param: 'kernel.enabled_locales')]
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
        $this->logger = new NullLogger();
    }

    /**
     * @return iterable<string, Data>
     */
    public function getFiles(): iterable
    {
        $this->logger->debug('Compiling manifest files.', [
            'manifest' => $this->manifest,
        ]);
        if ($this->manifest->enabled === false) {
            $this->logger->debug('Manifest is disabled. No file to compile.');
            yield from [];

            return;
        }
        if ($this->locales === []) {
            $this->logger->debug('No locale defined. Compiling default manifest.');
            $manifest = $this->compileManifest(null);
            yield $manifest->url => $manifest;
        }
        foreach ($this->locales as $locale) {
            $this->logger->debug('Compiling manifest for locale.', [
                'locale' => $locale,
            ]);
            $manifest = $this->compileManifest($locale);
            yield $manifest->url => $manifest;
        }
        $this->logger->debug('Manifest files compiled.');
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function compileManifest(null|string $locale): Data
    {
        $this->logger->debug('Compiling manifest.', [
            'locale' => $locale,
        ]);
        $manifest = clone $this->manifest;
        $manifestPublicUrl = $this->manifestPublicUrl;
        if ($locale !== null) {
            $manifestPublicUrl = str_replace('{locale}', $locale, $this->manifestPublicUrl);
        }

        $callback = function () use ($manifest, $locale): string {
            $preEvent = new PreManifestCompileEvent($manifest);
            $preEvent = $this->dispatcher->dispatch($preEvent);
            assert($preEvent instanceof PreManifestCompileEvent);

            $options = $this->jsonOptions;
            if ($locale !== null) {
                $options[TranslatableNormalizer::NORMALIZATION_LOCALE_KEY] = $locale;
            }
            $data = $this->serializer->serialize($preEvent->manifest, 'json', $options);
            $postEvent = new PostManifestCompileEvent($manifest, $data);
            $postEvent = $this->dispatcher->dispatch($postEvent);
            assert($postEvent instanceof PostManifestCompileEvent);

            return $postEvent->data;
        };

        return Data::create(
            $manifestPublicUrl,
            $callback,
            [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'application/manifest+json',
                'X-Pwa-Dev' => true,
            ]
        );
    }
}
