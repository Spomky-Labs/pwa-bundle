<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use InvalidArgumentException;
use Nelmio\SecurityBundle\EventListener\ContentSecurityPolicyListener;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\Dto\Icon;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\MimeTypes;
use function array_key_exists;
use function sprintf;
use const ENT_COMPAT;
use const ENT_SUBSTITUTE;
use const PHP_EOL;

final readonly class PwaRuntime
{
    private string $manifestPublicUrl;

    public function __construct(
        private AssetMapperInterface $assetMapper,
        private Manifest $manifest,
        private Favicons $favicons,
        private FaviconsCompiler $faviconsCompiler,
        #[Autowire(param: 'spomky_labs_pwa.manifest.public_url')]
        string $manifestPublicUrl,
        #[Autowire(service: 'nelmio_security.csp_listener')]
        private ?ContentSecurityPolicyListener $cspListener = null,
    ) {
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
    }

    /**
     * @param array<string, bool|int|string|null|float> $swAttributes
     */
    public function load(
        bool $injectThemeColor = true,
        bool $injectIcons = true,
        bool $injectFavicons = true,
        bool $injectSW = true,
        array $swAttributes = [],
        null|string $locale = null,
    ): string {
        $output = '';
        if ($this->manifest->enabled === true) {
            $output = $this->injectManifestFile($output, $locale);
        }
        if ($this->manifest->serviceWorker?->enabled === true) {
            $output = $this->injectServiceWorker($output, $injectSW, $swAttributes);
        }
        $output = $this->injectIcons($output, $injectIcons);
        $output = $this->injectFavicons($output, $injectFavicons);

        return $this->injectThemeColor($output, $injectThemeColor);
    }

    private function injectManifestFile(string $output, null|string $locale): string
    {
        $manifestPublicUrl = $locale === null ? $this->manifestPublicUrl : str_replace(
            '{locale}',
            $locale,
            $this->manifestPublicUrl
        );
        $url = $this->assetMapper->getPublicPath($manifestPublicUrl) ?? $manifestPublicUrl;
        $useCredentials = '';
        if ($this->manifest->useCredentials === true) {
            $useCredentials = ' crossorigin="use-credentials"';
        }

        return $output . sprintf('%s<link rel="manifest" href="%s"%s>', PHP_EOL, $url, $useCredentials);
    }

    private function injectThemeColor(string $output, bool $themeColor): string
    {
        if ($this->manifest->themeColor === null || $themeColor === false) {
            return $output;
        }
        $colors = [
            'light' => [$this->manifest->themeColor],
        ];
        if ($this->manifest->darkThemeColor !== null) {
            $colors['light'] = [
                $this->manifest->themeColor,
                'media' => ' media="(prefers-color-scheme: light)"',
            ];
            $colors['dark'] = [
                $this->manifest->darkThemeColor,
                'media' => ' media="(prefers-color-scheme: dark)"',
            ];
        }
        foreach ($colors as $color) {
            $media = $color['media'] ?? '';
            $output .= sprintf('%s<meta name="theme-color" content="%s" %s>', PHP_EOL, $color[0], $media);
        }

        return $output;
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
            $workboxUrl = sprintf('%s%s', $serviceWorker->workbox->workboxPublicUrl, '/workbox-window.prod.mjs');
            $declaration = <<<SERVICE_WORKER
<script type="module"{$scriptAttributes}>
  import {Workbox} from '{$workboxUrl}';
  if ('serviceWorker' in navigator) {
    const wb = new Workbox('{$url}'{$registerOptions});
    wb.register();
    window.workbox = wb;
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
            ['url' => $url, 'type' => $type] = $this->getIconInfo($icon);
            $attributes = sprintf(
                'rel="%s" sizes="%s" href="%s"',
                str_contains($icon->purpose ?? '', 'maskable') ? 'mask-icon' : 'icon',
                $icon->getSizeList(),
                $url
            );
            if ($type !== null) {
                $attributes .= sprintf(' type="%s"', $type);
            }

            $output .= sprintf('%s<link %s>', PHP_EOL, $attributes);
        }

        return $output;
    }

    /**
     * @return array{url: string, type: string|null}
     */
    private function getIconInfo(Icon $icon): array
    {
        $url = null;
        $type = $icon->type;
        if ($type === null && ! str_starts_with($icon->src->src, '/')) {
            $asset = $this->assetMapper->getAsset($icon->src->src);
            $url = $asset?->publicPath;
            $type = $this->getFormat($asset);
        }
        if ($url === null) {
            $url = $icon->src->src;
        }

        return [
            'url' => $url,
            'type' => $type,
        ];
    }

    private function getFormat(?MappedAsset $asset): ?string
    {
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
        if (! array_key_exists('nonce', $attributes) && $this->cspListener !== null) {
            $nonce = $this->cspListener->getNonce('script');
            $attributes['nonce'] = $nonce;
        } elseif (array_key_exists('nonce', $attributes) && $attributes['nonce'] === false) {
            unset($attributes['nonce']);
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

    private function injectFavicons(string $output, bool $injectFavicons): string
    {
        if ($this->favicons->enabled === false || $injectFavicons === false) {
            return $output;
        }

        $files = $this->faviconsCompiler->getFiles();
        foreach ($files as $file) {
            if ($file->html === null) {
                continue;
            }

            $output .= PHP_EOL . $file->html;
        }

        if ($this->favicons->tileColor !== null) {
            $output .= PHP_EOL . sprintf(
                '<meta name="msapplication-TileColor" content="%s">',
                $this->favicons->tileColor
            );
            /*$output .= PHP_EOL . sprintf(
                '<meta name="msapplication-TileImage" content="%s">',
                $files['/pwa/icon-144x144.png']->url
            );*/
        }

        return $output;
    }
}
