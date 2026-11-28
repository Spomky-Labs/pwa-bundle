<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Twig\PwaRuntime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * With Workbox, the registration script reloads the page when a new service worker takes control of it.
 * controllerchange also fires when clients.claim() (skip_waiting: true) makes the very first service worker take
 * control of a page that had none. A first visit is not an update and must not reload the page.
 *
 * @see https://github.com/Spomky-Labs/pwa-bundle/issues/489
 *
 * @internal
 */
final class ServiceWorkerRegistrationTest extends KernelTestCase
{
    #[Test]
    public function thePageIsNotReloadedWhenNoServiceWorkerControlledItBefore(): void
    {
        // Given
        $output = $this->renderRegistrationScript();

        // Then
        static::assertStringContainsString(<<<'JS'
      let controlled = navigator.serviceWorker.controller !== null;
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!controlled) {
          controlled = true;
          return;
        }
JS
            , $output);

        // The controller is read before the service worker is registered.
        $controllerRead = mb_strpos($output, 'let controlled = navigator.serviceWorker.controller !== null;');
        $registration = mb_strpos($output, 'await wb.register();');
        static::assertIsInt($controllerRead);
        static::assertIsInt($registration);
        static::assertLessThan($registration, $controllerRead);
    }

    #[Test]
    public function thePageIsReloadedOnceWhenAnotherServiceWorkerTakesControl(): void
    {
        // Given
        $output = $this->renderRegistrationScript();

        // Then
        static::assertStringContainsString(<<<'JS'
          controlled = true;
          return;
        }
        if (!refreshing) {
          const event = new CustomEvent('sw:controller-changed', { detail: { wb }, cancelable: true });
          window.dispatchEvent(event);
          if (!event.defaultPrevented) {
            window.location.reload();
            refreshing = true;
          }
        }
      });
JS
            , $output);
        static::assertSame(1, mb_substr_count($output, 'window.location.reload()'));
    }

    /**
     * The application is told before the page is reloaded, the same way it is told an update is waiting. It may
     * call preventDefault() to keep the page, e.g. while a form is being filled, and reload it later.
     */
    #[Test]
    public function theApplicationCanPreventTheReload(): void
    {
        // Given
        $output = $this->renderRegistrationScript();

        // Then
        static::assertStringContainsString(<<<'JS'
          const event = new CustomEvent('sw:controller-changed', { detail: { wb }, cancelable: true });
          window.dispatchEvent(event);
          if (!event.defaultPrevented) {
            window.location.reload();
JS
            , $output);

        // The existing event and the global are left as they are.
        static::assertStringContainsString(<<<'JS'
      wb.addEventListener('waiting', () => {
        const event = new CustomEvent('sw:update-available', { detail: { wb } });
        window.dispatchEvent(event);
      });
JS
            , $output);
        static::assertStringContainsString('window.workbox = wb;', $output);
    }

    private function renderRegistrationScript(): string
    {
        self::bootKernel();
        $runtime = self::getContainer()->get(PwaRuntime::class);
        static::assertInstanceOf(PwaRuntime::class, $runtime);

        return $runtime->load(
            injectThemeColor: false,
            injectFavicons: false,
            injectResourceHints: false,
            injectSpeculationRules: false
        );
    }
}
