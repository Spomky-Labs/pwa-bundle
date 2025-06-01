<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class BackgroundFetchCache implements ServiceWorkerRuleInterface
{
    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker,
        private RouterInterface $router,
        private TranslatorInterface $translator,
    ) {
        $this->workbox = $serviceWorker->workbox;
    }

    public function process(bool $debug = false): string
    {
        if (! $this->workbox->backgroundFetch->enabled) {
            return '';
        }

        $declaration = '';

        if ($this->workbox->backgroundFetch->successUrl !== null) {
            $path = $this->workbox->backgroundFetch->successUrl->path;
            $successUrl = str_starts_with($path, '/') ? $path : $this->router->generate(
                $path,
                $this->workbox->backgroundFetch->successUrl->params,
                $this->workbox->backgroundFetch->successUrl->pathTypeReference
            );
            $declaration .= <<<BACKGROUND_FETCH_CACHE
registerBackgroundFetchTask('click', async (event) => {
  const bgFetch = event.registration;
  if (bgFetch.result !== 'success') {
    return;
  }
  clients.openWindow('{$successUrl}');
}, 50);

BACKGROUND_FETCH_CACHE;
        }

        if ($this->workbox->backgroundFetch->progressUrl !== null) {
            $path = $this->workbox->backgroundFetch->progressUrl->path;
            $progressUrl = str_starts_with($path, '/') ? $path : $this->router->generate(
                $path,
                $this->workbox->backgroundFetch->progressUrl->params,
                $this->workbox->backgroundFetch->progressUrl->pathTypeReference
            );
            $declaration .= <<<BACKGROUND_FETCH_CACHE

registerBackgroundFetchTask('click', async (event) => {
  const bgFetch = event.registration;
  if (bgFetch.result === 'success') {
    return;
  }
  clients.openWindow('{$progressUrl}');
}, 50);

BACKGROUND_FETCH_CACHE;
        }

        $successMessage = $this->workbox->backgroundFetch->successMessage ?? '{title} ✅';
        if ($successMessage !== '' && $successMessage !== null) {
            $successMessage = $this->translator->trans($successMessage, [], 'pwa');
        }
        $declaration .= <<<BACKGROUND_FETCH_CACHE
registerBackgroundFetchTask('success', async (event) => {
  const records = await event.registration.matchAll();
  const db = await openBackgroundFetchDatabase();
  const id = event.registration.id;
  const meta = bgFetchMetadata.get(id) || {};

  for (const record of records) {
    const response = await record.responseReady;
    const url = new URL(record.request.url);
    const fileId = url.pathname;
    const contentType = response.headers.get('Content-Type');

    const oldChunks = await db.getAllFromIndex('chunks', 'by-id', IDBKeyRange.only(fileId));
    for (const chunk of oldChunks) {
      await db.delete('chunks', [chunk.id, chunk.index]);
    }
    await db.delete('files', fileId);

    const reader = response.body.getReader();
    let i = 0;

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      await db.put('chunks', { id: fileId, index: i++, chunk: value });
    }

    await db.put('files', {
      id: fileId,
      name: url.pathname.split('/').pop(),
      contentType,
      totalChunks: i,
      meta,
    });
  }
  
  const rawMessage = '{$successMessage}';
  const originalTitle = meta.title ?? 'Done';
  const finalTitle = rawMessage.replace('{title}', originalTitle);

  try {
    await event.updateUI({
      ...meta,
      title: finalTitle,
    });
  } catch (e) {
    console.warn("updateUI failed during success event", e);
  }

  console.log(`[SW] Stored \${records.length} file(s) from background fetch \${id} in IndexedDB`);
});

BACKGROUND_FETCH_CACHE;

        $failureMessage = $this->workbox->backgroundFetch->failureMessage ?? '{title} ❌';
        if ($failureMessage !== '' && $failureMessage !== null) {
            $failureMessage = $this->translator->trans($failureMessage, [], 'pwa');
        }

        return $declaration . <<<BACKGROUND_FETCH_CACHE
registerBackgroundFetchTask('fail', async (event) => {
  const registration = event.registration;

  const meta = bgFetchMetadata.get(registration.id) || {};
  console.error(meta);
  bgFetchMetadata.delete(registration.id);

  const rawMessage = '{$failureMessage}';
  const originalTitle = meta.title ?? 'Failed';
  const finalTitle = rawMessage.replace('{title}', originalTitle);

  try {
    await event.updateUI({
      ...meta,
      title: finalTitle,
    });
  } catch (e) {
    console.warn("updateUI failed during fail event", e);
  }
});

BACKGROUND_FETCH_CACHE;
    }
}
