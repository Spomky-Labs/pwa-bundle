<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class BackgroundFetchCache implements ServiceWorkerRuleInterface
{
    public function __construct(
        private ServiceWorker $serviceWorker,
        private RouterInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    public function process(bool $debug = false): string
    {
        if (! $this->serviceWorker->backgroundFetch->enabled) {
            return '';
        }

        $declaration = '';

        if ($this->serviceWorker->backgroundFetch->successUrl !== null) {
            $successUrl = $this->router->generate(
                $this->serviceWorker->backgroundFetch->successUrl->path,
                $this->serviceWorker->backgroundFetch->successUrl->params,
                $this->serviceWorker->backgroundFetch->successUrl->pathTypeReference
            );
            $declaration .= <<<BACKGROUND_FETCH_CACHE

addEventListener('backgroundfetchclick', (event) => {
  const bgFetch = event.registration;
  if (bgFetch.result !== 'success') {
    return;
  }
  clients.openWindow('{$successUrl}');
});

BACKGROUND_FETCH_CACHE;
        }

        if ($this->serviceWorker->backgroundFetch->progressUrl !== null) {
            $progressUrl = $this->router->generate(
                $this->serviceWorker->backgroundFetch->progressUrl->path,
                $this->serviceWorker->backgroundFetch->progressUrl->params,
                $this->serviceWorker->backgroundFetch->progressUrl->pathTypeReference
            );
            $declaration .= <<<BACKGROUND_FETCH_CACHE

addEventListener('backgroundfetchclick', (event) => {
  const bgFetch = event.registration;
  if (bgFetch.result === 'success') {
    return;
  }
  clients.openWindow('{$progressUrl}');
});

BACKGROUND_FETCH_CACHE;
        }

        if ($this->serviceWorker->backgroundFetch->successMessage !== null) {
            $successMessage = $this->serviceWorker->backgroundFetch->successMessage;
            if ($successMessage !== '' && $successMessage !== null) {
                $successMessage = $this->translator->trans($successMessage, [], 'pwa');
            }
            $declaration .= <<<BACKGROUND_FETCH_CACHE

addEventListener("backgroundfetchsuccess", (event) => {
  event.updateUI({ title: "{$successMessage}" });
});

BACKGROUND_FETCH_CACHE;
        }

        if ($this->serviceWorker->backgroundFetch->failureMessage !== null) {
            $failureMessage = $this->serviceWorker->backgroundFetch->failureMessage;
            if ($failureMessage !== '' && $failureMessage !== null) {
                $failureMessage = $this->translator->trans($failureMessage, [], 'pwa');
            }
            $declaration .= <<<BACKGROUND_FETCH_CACHE

addEventListener("backgroundfetchfail", (event) => {
  event.updateUI({ title: "{$failureMessage}" });
});

BACKGROUND_FETCH_CACHE;
        }

        return $declaration;
    }

    public static function getPriority(): int
    {
        return 1024;
    }
}
