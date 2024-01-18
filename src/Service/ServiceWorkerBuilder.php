<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\SerializerInterface;
use function assert;
use function is_string;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class ServiceWorkerBuilder
{
    private ?string $serviceWorkerPublicUrl;

    public function __construct(
        private SerializerInterface $serializer,
        private Manifest $manifest,
        private AssetMapperInterface $assetMapper,
        #[Autowire('%spomky_labs_pwa.serviceworker.precaching_placeholder%')]
        private string $precachingPlaceholder,
    ) {
        $serviceWorkerPublicUrl = $manifest->serviceWorker?->dest;
        $this->serviceWorkerPublicUrl = $serviceWorkerPublicUrl === null ? null : '/' . trim(
            $serviceWorkerPublicUrl,
            '/'
        );
    }

    public function build(): ?string
    {
        if ($this->serviceWorkerPublicUrl === null) {
            return null;
        }
        $serviceWorkerSource = $this->manifest->serviceWorker?->src;
        if ($serviceWorkerSource === null) {
            return null;
        }

        if (! str_starts_with($serviceWorkerSource, '/')) {
            $asset = $this->assetMapper->getAsset($serviceWorkerSource);
            assert($asset !== null, 'Unable to find service worker source asset');
            $body = $asset->content ?? file_get_contents($asset->sourcePath);
        } else {
            $body = file_get_contents($serviceWorkerSource);
        }
        assert(is_string($body), 'Unable to find service worker source content');
        return $this->processPrecachedAssets($body);
    }

    private function processPrecachedAssets(string $body): string
    {
        if (! str_contains($body, $this->precachingPlaceholder)) {
            return $body;
        }
        $result = [];
        foreach ($this->assetMapper->allAssets() as $asset) {
            $result[] = [
                'url' => $asset->publicPath,
                'revision' => $asset->digest,
            ];
        }
        return str_replace($this->precachingPlaceholder, $this->serializer->serialize($result, 'json', [
            'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ]), $body);
    }
}
