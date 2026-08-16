<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Twig;

use function array_key_exists;
use const ENT_COMPAT;
use const ENT_SUBSTITUTE;
use function in_array;
use InvalidArgumentException;
use Nelmio\SecurityBundle\EventListener\ContentSecurityPolicyListener;
use const PHP_EOL;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\FaviconsBuilder;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use SpomkyLabs\PwaBundle\Service\ResourceHintsBuilder;
use SpomkyLabs\PwaBundle\Service\SpeculationRulesBuilder;
use function sprintf;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\WebLink\GenericLinkProvider;

final readonly class PwaRuntime
{
    private string $manifestPublicUrl;

    private Manifest $manifest;

    private Favicons $favicons;

    public function __construct(
        private AssetMapperInterface $assetMapper,
        ManifestBuilder $manifestBuilder,
        FaviconsBuilder $faviconsBuilder,
        private FaviconsCompiler $faviconsCompiler,
        #[Autowire(param: 'spomky_labs_pwa.manifest.public_url')]
        string $manifestPublicUrl,
        private RequestStack $requestStack,
        private ResourceHintsBuilder $resourceHintsBuilder,
        private SpeculationRulesBuilder $speculationRulesBuilder,
        private BasePathResolver $basePathResolver,
        #[Autowire(service: 'nelmio_security.csp_listener')]
        private ?ContentSecurityPolicyListener $cspListener = null,
    ) {
        $this->favicons = $faviconsBuilder->create();
        $this->manifest = $manifestBuilder->create();
        $this->manifestPublicUrl = '/' . trim($manifestPublicUrl, '/');
    }

    /**
     * @param array<string, bool|int|string|null|float> $swAttributes
     * @param bool $injectIcons Deprecated
     */
    public function load(
        bool $injectThemeColor = true,
        bool $injectIcons = false,
        bool $injectFavicons = true,
        bool $injectSW = true,
        array $swAttributes = [],
        null|string $locale = null,
        bool $injectResourceHints = true,
        bool $injectSpeculationRules = true,
    ): string {
        $output = '';
        $output = $this->injectResourceHints($output, $injectResourceHints);
        if ($this->manifest->enabled) {
            $output = $this->injectManifestFile($output, $locale);
        }
        if ($this->manifest->serviceWorker?->enabled === true) {
            $output = $this->injectServiceWorker($output, $injectSW, $swAttributes);
        }
        $output = $this->injectFavicons($output, $injectFavicons);
        $output = $this->injectThemeColor($output, $injectThemeColor);
        $output = $this->injectMobileWebAppCapable($output);

        return $this->injectSpeculationRules($output, $injectSpeculationRules);
    }

    private function injectManifestFile(string $output, null|string $locale): string
    {
        $locale = $this->getLocale($locale);
        $manifestPublicUrl = $locale === null ? $this->manifestPublicUrl : str_replace(
            '{locale}',
            $locale,
            $this->manifestPublicUrl
        );
        $url = $this->basePathResolver->prefix(
            $this->assetMapper->getPublicPath($manifestPublicUrl) ?? $manifestPublicUrl
        );
        if ($this->manifest->useCredentials) {
            $useCredentials = ' crossorigin="use-credentials"';
        } else {
            $useCredentials = ' crossorigin="anonymous"';
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
        $url = $this->basePathResolver->prefix('/' . trim($serviceWorker->dest, '/'));
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
        if ($serviceWorker->workbox->enabled) {
            $workboxUrl = $this->basePathResolver->prefix(sprintf(
                '%s%s',
                '/' . trim($serviceWorker->workbox->config->workboxPublicUrl, '/'),
                '/workbox-window.prod.umd.js'
            ));
            // Using UMD version instead of ESM to avoid importmap dependency issues
            // This prevents "bare specifier not remapped" errors regardless of script order.
            // See: https://github.com/Spomky-Labs/pwa-bundle/pull/393
            // CSP compatibility: dynamically loading workbox-window inside the inline script
            // because inline event handlers (onload) are blocked by CSP even with nonces,
            // and defer is ignored on inline scripts.
            $declaration = <<<SERVICE_WORKER
<script{$scriptAttributes}>
(async () => {
  await new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = '{$workboxUrl}';
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
  });

  if ('serviceWorker' in navigator) {
    try {
      const wb = new workbox.Workbox('{$url}'{$registerOptions});

      wb.addEventListener('waiting', () => {
        const event = new CustomEvent('sw:update-available', { detail: { wb } });
        window.dispatchEvent(event);
      });

      let refreshing = false;
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!refreshing) {
          window.location.reload();
          refreshing = true;
        }
      });

      await wb.register();
      window.workbox = wb;
    } catch (e) {
      console.error('SW registration failed', e);
    }
  }
})();
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
        }

        return $output;
    }

    private function injectMobileWebAppCapable(string $output): string
    {
        if ($this->manifest->enabled === false || $this->manifest->display === null) {
            return $output;
        }

        // Add mobile-web-app-capable meta tag for PWA display modes
        $pwaDisplayModes = ['standalone', 'fullscreen', 'minimal-ui'];
        if (in_array($this->manifest->display, $pwaDisplayModes, true)) {
            $output .= sprintf('%s<meta name="mobile-web-app-capable" content="yes">', PHP_EOL);
        }

        return $output;
    }

    private function getLocale(null|string $locale = null): null|string
    {
        return $locale ?? $this->requestStack->getMainRequest()?->getLocale();
    }

    private function injectResourceHints(string $output, bool $injectResourceHints): string
    {
        if ($injectResourceHints === false || ! $this->resourceHintsBuilder->isEnabled()) {
            return $output;
        }

        // Add Link headers to the request for HTTP/2 Server Push / Early Hints
        $this->addResourceHintsToRequest();

        // Generate HTML link tags
        return $output . $this->resourceHintsBuilder->generateHtml();
    }

    private function addResourceHintsToRequest(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $links = $this->resourceHintsBuilder->getLinks();
        if ($links === []) {
            return;
        }

        $linkProvider = $request->attributes->get('_links');
        if (! $linkProvider instanceof GenericLinkProvider) {
            $linkProvider = new GenericLinkProvider();
        }

        foreach ($links as $link) {
            $linkProvider = $linkProvider->withLink($link);
        }

        $request->attributes->set('_links', $linkProvider);
    }

    private function injectSpeculationRules(string $output, bool $injectSpeculationRules): string
    {
        if ($injectSpeculationRules === false || ! $this->speculationRulesBuilder->isEnabled()) {
            return $output;
        }

        $json = $this->speculationRulesBuilder->generateJson();

        if ($json === null) {
            return $output;
        }

        return $output . sprintf('%s<script type="speculationrules">%s</script>', PHP_EOL, $json);
    }
}
