<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use SpomkyLabs\PwaBundle\Service\Plugin\CachePlugin;
use function in_array;

final readonly class WorkboxCacheStrategy extends CacheStrategy
{
    /**
     * @param array<CachePlugin> $plugins
     * @param array<string> $preloadUrls
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $name,
        public string $strategy,
        public string $matchCallback,
        bool $enabled,
        bool $requireWorkbox,
        public null|string $method = null,
        public array $plugins = [],
        public array $preloadUrls = [],
        public array $options = [],
    ) {
        parent::__construct($name, $enabled, $requireWorkbox);
    }

    /**
     * @param array<CachePlugin> $plugins
     * @param array<string> $preloadUrls
     * @param array<string, mixed> $options
     */
    public static function create(
        string $name,
        string $strategy,
        string $matchCallback,
        bool $enabled,
        bool $requireWorkbox,
        null|string $method = null,
        array $plugins = [],
        array $preloadUrls = [],
        array $options = [],
    ): static {
        return new static(
            $name,
            $strategy,
            $matchCallback,
            $enabled,
            $requireWorkbox,
            $method,
            $plugins,
            $preloadUrls,
            $options
        );
    }

    public function render(string $cacheObjectName, int $jsonOptions = 0): string
    {
        if ($this->enabled === false) {
            return '';
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
            $cacheName = "cacheName: '{$cacheName}',";
        }
        $plugins = sprintf('[%s]', implode(', ', array_map(
            fn (CachePlugin $plugin) => $plugin->render($jsonOptions),
            $this->plugins
        )));

        $declaration = <<<FONT_CACHE_RULE_STRATEGY
const {$cacheObjectName} = new workbox.strategies.{$this->strategy}({
  {$timeout}{$cacheName}plugins: {$plugins}
});
workbox.routing.registerRoute(
  {$this->matchCallback},
  {$cacheObjectName}
);
FONT_CACHE_RULE_STRATEGY;

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

        return trim($declaration);
    }
}
