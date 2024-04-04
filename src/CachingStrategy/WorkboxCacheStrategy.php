<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\WorkboxPlugin\CachePluginInterface;
use function in_array;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class WorkboxCacheStrategy implements CacheStrategyInterface
{
    private null|string $name = null;

    private null|string $method = null;

    /**
     * @var array<CachePluginInterface>
     */
    private array $plugins = [];

    /**
     * @var array<string>
     */
    private array $preloadUrls = [];

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public function __construct(
        public readonly bool $enabled,
        public readonly bool $needsWorkbox,
        public readonly string $strategy,
        public readonly string $matchCallback,
    ) {
    }

    public static function create(
        bool $enabled,
        bool $requireWorkbox,
        string $strategy,
        string $matchCallback,
    ): static {
        return new static($enabled, $requireWorkbox, $strategy, $matchCallback,);
    }

    public function withName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function withMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function withPlugin(CachePluginInterface $plugin, CachePluginInterface ...$plugins): static
    {
        $this->plugins = array_merge([$plugin], $plugins);
        return $this;
    }

    public function withPreloadUrl(string $preloadUrl, string ...$preloadUrls): static
    {
        $this->preloadUrls = array_merge([$preloadUrl], $preloadUrls);
        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function needsWorkbox(): bool
    {
        return $this->needsWorkbox;
    }

    public function render(string $cacheObjectName, bool $debug = false): string
    {
        if ($this->enabled === false) {
            return '';
        }
        $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
        if ($debug === true) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        $timeout = '';
        if (in_array(
            $this->strategy,
            [self::STRATEGY_NETWORK_FIRST, self::STRATEGY_NETWORK_ONLY],
            true
        ) && ($this->options['networkTimeoutSeconds'] ?? null) !== null) {
            $timeout = "networkTimeoutSeconds: {$this->options['networkTimeoutSeconds']},";
        }
        $cacheName = '';
        if ($this->strategy !== self::STRATEGY_NETWORK_ONLY) {
            $cacheName = sprintf("cacheName: '%s',", $this->getName() ?? $cacheObjectName);
        }
        $plugins = sprintf('[%s]', implode(', ', array_map(
            fn (CachePluginInterface $plugin) => $plugin->render($jsonOptions),
            $this->plugins
        )));
        $method = $this->method !== null ? ",'{$this->method}'" : '';

        $declaration = '';
        if ($debug) {
            $declaration .= <<<DEBUG_STATEMENT


/**************************************************** CACHE STRATEGY ****************************************************/
// Strategy: {$this->strategy}
// Match: {$this->matchCallback}
// Cache Name: {$this->getName()}
// Enabled: {$this->enabled}
// Needs Workbox: {$this->needsWorkbox()}
// Method: {$this->method}

// 1. Creation of the Workbox Cache Strategy object
// 2. Register the route with the Workbox Router
// 3. Add the assets to the cache when the service worker is installed

DEBUG_STATEMENT;
        }

        $declaration .= <<<ROUTE_REGISTRATION
const {$cacheObjectName} = new workbox.strategies.{$this->strategy}({
  {$timeout}{$cacheName}plugins: {$plugins}
});
workbox.routing.registerRoute({$this->matchCallback},{$cacheObjectName}{$method});

ROUTE_REGISTRATION;

        if ($this->preloadUrls !== []) {
            $fontUrls = json_encode($this->preloadUrls, $jsonOptions);
            $declaration .= <<<ASSET_CACHE_RULE_PRELOAD
self.addEventListener('install', event => {
  const done = {$fontUrls}.map(
    path =>
      {$cacheObjectName}.handleAll({
        event,
        request: new Request(path),
      })[1]
  );
  event.waitUntil(Promise.all(done));
});


ASSET_CACHE_RULE_PRELOAD;
        }

        if ($debug === true) {
            $declaration .= <<<DEBUG_STATEMENT
/**************************************************** END CACHE STRATEGY ****************************************************/



DEBUG_STATEMENT;
        }

        return $debug === true ? $declaration : trim($declaration);
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @return array<CachePluginInterface>
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @return array<string>
     */
    public function getPreloadUrls(): array
    {
        return $this->preloadUrls;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
