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
  if (!(event instanceof ExtendableEvent)) {
    throw new Error("precacheResources needs a valid ExtendableEvent");
  }
  return Promise.all(resourceList.map(path =>
    strategy.handleAll({
      event,
      request: new Request(path),
    })[1]
  ));
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

const messageTasks = [];
function registerMessageTask(callback) {
  messageTasks.push(callback);
}

self.addEventListener('message', (event) => {
  event.waitUntil(
    messageTasks.reduce(
      (chain, task) => chain.then(() => task(event)),
      Promise.resolve()
    )
  );
});

const installTasks = [];
function registerInstallTask(callback, priority = 100) {
  installTasks.push({
    callback: (event) => {
      const result = callback(event);
      if (!result?.then) console.warn("Install task did not return a Promise");
      return result;
    },
    priority,
  });
}
self.addEventListener('install', (event) => {
  event.waitUntil(
    installTasks
      .sort((a, b) => a.priority - b.priority)
      .reduce(
        (chain, task) => chain.then(() => task.callback(event)),
        Promise.resolve()
      )
  );
});

function statusGuard(min, max) {
  return {
    fetchDidSucceed: ({ response }) => {
      if (response.status >= min && response.status <= max) {
        throw new Error(`Server error: \${response.status}`);
      }
      return response;
    }
  };
}

registerMessageTask(async (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    await self.skipWaiting();
  }
});

const usedCacheNames = new Set();
function registerCacheName(name) {
  usedCacheNames.add(name);
  return name;
}

async function openBackgroundFetchDatabase() {
  return await self.idb.openDB('{$this->workbox->backgroundFetch->dbName}', 1, {
    upgrade(db) {
      if (!db.objectStoreNames.contains('files')) {
        db.createObjectStore('files', { keyPath: 'id' });
      }

      if (!db.objectStoreNames.contains('chunks')) {
        const store = db.createObjectStore('chunks', { keyPath: ['id', 'index'] });
        store.createIndex('by-id', 'id');
      } else {
        const store = db.transaction.objectStore('chunks');
        if (!Array.from(store.indexNames).includes('by-id')) {
          store.createIndex('by-id', 'id');
        }
      }
    }
  });
}

const bgFetchMetadata = new Map();
const bgFetchChannel = new BroadcastChannel('bg-fetch');
bgFetchChannel.onmessage = async (event) => {
  const { type, id, meta } = event.data || {};

  switch (type) {
    case 'register-meta':
      bgFetchMetadata.set(id, meta);
      break;

    case 'get-meta':
      const metadata = bgFetchMetadata.get(id) || null;
      bgFetchChannel.postMessage({ type: 'meta-response', id, metadata });
      break;

    case 'clear-meta':
      bgFetchMetadata.delete(id);
      break;
      
    case 'list-stored-files':
      {
        const db = await openBackgroundFetchDatabase();
        const files = await db.getAll('files');
        bgFetchChannel.postMessage({ type: 'stored-files', files });
        break;
      }
    
    case 'delete-stored-file':
      {
        const name = event.data.name;
        const db = await openBackgroundFetchDatabase();
        const allFiles = await db.getAll('files');
        const target = allFiles.find(f => f.name === name);
        if (target) {
          await db.delete('files', target.id);
          let index = 0;
          while (await db.get('chunks', [target.id, index])) {
            await db.delete('chunks', [target.id, index++]);
          }
        }
        break;
      }
  }
};

const backgroundFetchTasks = {
  click: [],
  success: [],
  fail: [],
};
function registerBackgroundFetchTask(type, callback, priority = 100) {
  if (!backgroundFetchTasks[type]) {
    throw new Error(`Unknown background fetch event type: \${type}`);
  }

  backgroundFetchTasks[type].push({
    callback: (event) => {
      const result = callback(event);
      if (!result?.then) {
        console.warn(`[\${type}] task did not return a Promise`);
      }
      return result;
    },
    priority,
  });
}

function runBackgroundFetchTasks(type, event) {
  const tasks = backgroundFetchTasks[type] ?? [];
  return tasks
    .sort((a, b) => a.priority - b.priority)
    .reduce(
      (chain, task) => chain.then(() => task.callback(event)),
      Promise.resolve()
    );
}

self.addEventListener('backgroundfetchclick', (event) => {
  event.waitUntil(runBackgroundFetchTasks('click', event));
});

self.addEventListener('backgroundfetchsuccess', (event) => {
  event.waitUntil(runBackgroundFetchTasks('success', event));
});

self.addEventListener('backgroundfetchfail', (event) => {
  event.waitUntil(runBackgroundFetchTasks('fail', event));
});


const pushTasks = [];
function registerPushTask(callback) {
  pushTasks.push(callback);
}
self.addEventListener('push', (event) => {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }
    event.waitUntil(
      pushTasks.reduce(
        (chain, task) => chain.then(() => task(event)),
        Promise.resolve()
      )
    );
});

const notificationActionHandlers = new Map();
function registerNotificationAction(actionName, handler) {
  notificationActionHandlers.set(actionName, handler);
}
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const action = event.action || "";

  const handler = notificationActionHandlers.get(action);
  if (typeof handler === 'function') {
    event.waitUntil(Promise.resolve(handler(event)));
  }
});
const structuredPushNotificationSupport = (event) => {
  const {data} = event;
  const sendNotification = response => {
    const {title, options} = JSON.parse(response);
    return self.registration.showNotification(title, options);
  };

  if (data) {
    const message = data.text();
    event.waitUntil(sendNotification(message));
  }
}
function simplePushNotificationSupport(event) {
  const { data } = event;

  if (!data) return;

  const message = data.text();
  const sendNotification = (text) => {
    return self.registration.showNotification('Notification', {
      body: text
    });
  };

  event.waitUntil(sendNotification(message));
}

CUSTOM_HELPERS;
    }

    public static function getPriority(): int
    {
        return 1023;
    }
}
