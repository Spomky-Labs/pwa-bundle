<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;

final class ClearCache implements ServiceWorkerRuleInterface, CanLogInterface
{
    private readonly Workbox $workbox;

    private LoggerInterface $logger;

    public function __construct(ServiceWorkerBuilder $serviceWorkerBuilder)
    {
        $this->workbox = $serviceWorkerBuilder->create()
            ->workbox;
        $this->logger = new NullLogger();
    }

    public function process(bool $debug = false): string
    {
        if ($this->workbox->enabled === false) {
            $this->logger->debug('Workbox is disabled. The rule will not be applied.');
            return '';
        }
        if ($this->workbox->clearCache === false) {
            $this->logger->debug(
                'Workbox is enabled but the cache is not set to be cleared. The rule will not be applied.'
            );
            return '';
        }

        $declaration = '';
        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT


/**************************************************** CACHE CLEAR ****************************************************/
// The configuration is set to clear the cache on each install event
// Caches registered through registerCacheName() are removed. To also remove a cache
// opened by the application, use registerClearCacheListener((names) => names.filter(...))

DEBUG_COMMENT;
        }

        $declaration .= <<<CLEAR_CACHE
registerInstallTask(async () => {
  const keys = await caches.keys();
  const doomed = new Set(keys.filter(k => usedCacheNames.has(k)));

  for (const task of clearCacheListeners) {
    try {
      for (const name of (await task(keys)) ?? []) {
        doomed.add(name);
      }
    } catch (e) {
      console.error('A clear cache listener failed', e);
    }
  }

  await Promise.all([...doomed].map(k => caches.delete(k)));
}, 0);

CLEAR_CACHE;

        if ($debug === true) {
            $declaration .= <<<DEBUG_COMMENT
/**************************************************** END CACHE CLEAR ****************************************************/




DEBUG_COMMENT;
        }
        $this->logger->debug('Cache clear rule added.', [
            'declaration' => $declaration,
        ]);

        return $declaration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
