<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;

final readonly class WorkboxHelpers implements ServiceWorkerRuleInterface
{
    private Workbox $workbox;

    public function __construct(ServiceWorker $serviceWorker)
    {
        $this->workbox = $serviceWorker->workbox;
    }

    public function process(bool $debug = false): string
    {
        if ($this->workbox->enabled === false) {
            return '';
        }
        return <<<CUSTOM_HELPERS
function registerCacheFirst(routeMatchFn, cacheName, plugins = []) {
  const strategy = new workbox.strategies.CacheFirst({ cacheName, plugins });
  workbox.routing.registerRoute(routeMatchFn, strategy);
  return strategy;
}

function precacheResources(strategy, resourceList, event) {
  const preload = resourceList.map(path =>
    strategy.handleAll({
      event,
      request: new Request(path),
    })[1]
  );
  return Promise.all(preload);
}

function createBackgroundSyncPlugin(queueName, maxRetentionTime = 2880, forceSyncFallback = false) {
  return new workbox.backgroundSync.BackgroundSyncPlugin(queueName, {
    maxRetentionTime,
    forceSyncFallback
  });
}

function createBackgroundSyncPluginWithBroadcast(queueName, channelName, maxRetentionTime = 2880, forceSyncFallback = false) {
  const queue = new workbox.backgroundSync.Queue(queueName, {
    maxRetentionTime,
    forceSyncFallback,
  });

  const bc = new BroadcastChannel(channelName);

  const replayQueueWithProgress = async () => {
    let entry;
    let successCount = 0;
    let failureCount = 0;
    const total = (await queue.getAll()).length;

    while ((entry = await queue.shiftRequest())) {
      try {
        await fetch(entry.request.clone());
        successCount++;
      } catch (error) {
        failureCount++;
        await queue.unshiftRequest(entry);
        throw error;
      } finally {
        const remaining = (await queue.getAll()).length;
        bc.postMessage({ name: queueName, replaying: true, remaining });
      }
    }

    const remaining = (await queue.getAll()).length;
    bc.postMessage({
      name: queueName,
      replayed: true,
      remaining,
      successCount,
      failureCount,
    });
  };

  bc.onmessage = async (event) => {
    if (event.data?.type === 'status-request') {
      const entries = await queue.getAll();
      bc.postMessage({ name: queueName, remaining: entries.length });
    }

    if (event.data?.type === 'replay-request') {
      try {
        await replayQueueWithProgress();
      } catch (error) {
        const entries = await queue.getAll();
        bc.postMessage({
          name: queueName,
          replayed: false,
          remaining: entries.length,
          error: error.message,
        });
      }
    }
  };

  return {
    fetchDidFail: async ({ request }) => {
      await queue.pushRequest({ request });
      const entries = await queue.getAll();
      bc.postMessage({ name: queueName, remaining: entries.length });
    },
    onSync: async () => {
      await replayQueueWithProgress();
    },
  };
}

CUSTOM_HELPERS;
    }

    public static function getPriority(): int
    {
        return 1023;
    }
}
