<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use InvalidArgumentException;
use SpomkyLabs\PwaBundle\Dto\Icon;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\MimeTypes;
use const ENT_COMPAT;
use const ENT_SUBSTITUTE;
use const PHP_EOL;

final readonly class PwaRuntime
{
    private string $manifestPublicUrl;

    public function __construct(
        #[Autowire('@asset_mapper.importmap.config_reader')]
        private ImportMapConfigReader $importMapConfigReader,
        private AssetMapperInterface $assetMapper,
        private Manifest $manifest,
        #[Autowire('%spomky_labs_pwa.manifest.public_url%')]
        string $manifestPublicUrl,
    ) {
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
    }

    /**
     * @param array<string, bool|int|string|null|float> $swAttributes
     */
    public function load(
        bool $injectThemeColor = true,
        bool $injectIcons = true,
        bool $injectSW = true,
        array $swAttributes = []
    ): string {
        $url = $this->assetMapper->getPublicPath($this->manifestPublicUrl) ?? $this->manifestPublicUrl;
        $output = sprintf('%s<link rel="manifest" href="%s">', PHP_EOL, $url);
        $output = $this->injectIcons($output, $injectIcons);
        $output = $this->injectThemeColor($output, $injectThemeColor);

        return $this->injectServiceWorker($output, $injectSW, $swAttributes);
    }

    private function injectThemeColor(string $output, bool $themeColor): string
    {
        if ($this->manifest->themeColor === null || $themeColor === false) {
            return $output;
        }

        return $output . sprintf('%s<meta name="theme-color" content="%s">', PHP_EOL, $this->manifest->themeColor);
    }

    /**
     * @param array<string, bool|int|string|null|float> $swAttributes
     */
    private function injectServiceWorker(string $output, bool $injectSW, array $swAttributes): string
    {
        $serviceWorker = $this->manifest->serviceWorker;
        if ($serviceWorker === null || $injectSW === false) {
            return $output;
        }
        $scriptAttributes = $this->createAttributesString($swAttributes);
        $url = $serviceWorker->dest;
        $registerOptions = '';
        if ($serviceWorker->scope !== null) {
            $registerOptions .= sprintf(", scope: '%s'", $serviceWorker->scope);
        }
        if ($serviceWorker->useCache !== null) {
            $registerOptions .= sprintf(', useCache: %s', $serviceWorker->useCache ? 'true' : 'false');
        }
        if ($registerOptions !== '') {
            $registerOptions = sprintf(', {%s}', mb_substr($registerOptions, 2));
        }
        if ($serviceWorker->workbox->enabled === true) {
            $hasWorkboxWindow = $this->importMapConfigReader->findRootImportMapEntry('workbox-window') !== null;
            $workboxUrl = $hasWorkboxWindow ? 'workbox-window' : 'https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-window.prod.mjs';
            $declaration = <<<SERVICE_WORKER
<script type="module" {$scriptAttributes}>
  import {Workbox} from '{$workboxUrl}';
  if ('serviceWorker' in navigator) {
    const wb = new Workbox('{$url}'{$registerOptions});
    wb.register();
  }
</script>
SERVICE_WORKER;
        } else {
            $declaration = <<<SERVICE_WORKER
<script {$scriptAttributes}>
    const registerServiceWorker = async () => {
      if ("serviceWorker" in navigator) {
        try {
          await navigator.serviceWorker.register('{$url}'{$registerOptions});
        } catch (error) {
          // Nothing to do
        }
      }
    };
    registerServiceWorker();
</script>
SERVICE_WORKER;
        }

        return $output . sprintf('%s%s', PHP_EOL, $declaration);
    }

    private function injectIcons(string $output, bool $injectIcons): string
    {
        if ($this->manifest->icons === [] || $injectIcons === false) {
            return $output;
        }
        foreach ($this->manifest->icons as $icon) {
            ['url' => $url, 'format' => $format] = $this->getIconInfo($icon);
            $attributes = sprintf(
                'rel="%s" sizes="%s" href="%s"',
                str_contains($icon->purpose ?? '', 'maskable') ? 'mask-icon' : 'icon',
                $icon->getSizeList(),
                $url
            );
            if ($format !== null) {
                $attributes .= sprintf(' type="%s"', $format);
            }

            $output .= sprintf('%s<link %s>', PHP_EOL, $attributes);
        }

        return $output;
    }

    /**
     * @return array{url: string, format: string|null}
     */
    private function getIconInfo(Icon $icon): array
    {
        $url = null;
        $format = $icon->format;
        if (! str_starts_with($icon->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($icon->src->src);
            $url = $asset?->publicPath;
            $format = $this->getFormat($icon, $asset);
        }
        if ($url === null) {
            $url = $icon->src->src;
        }

        return [
            'url' => $url,
            'format' => $format,
        ];
    }

    private function getFormat(Icon $object, ?MappedAsset $asset): ?string
    {
        if ($object->format !== null) {
            return $object->format;
        }

        if ($asset === null || ! class_exists(MimeTypes::class)) {
            return null;
        }

        $mime = MimeTypes::getDefault();
        return $mime->guessMimeType($asset->sourcePath);
    }

    /**
     * @param array<string, bool|int|string|null|float> $attributes
     */
    private function createAttributesString(array $attributes): string
    {
        $attributeString = '';
        if (isset($attributes['src']) || isset($attributes['type'])) {
            throw new InvalidArgumentException(sprintf(
                'The "src" and "type" attributes are not allowed on the <script> tag rendered by "%s".',
                self::class
            ));
        }
        foreach ($attributes as $name => $value) {
            $attributeString .= ' ';
            if ($value === true) {
                $attributeString .= $name;

                continue;
            }
            $attributeString .= sprintf(
                '%s="%s"',
                $name,
                htmlspecialchars((string) $value, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        return $attributeString;
    }
}
