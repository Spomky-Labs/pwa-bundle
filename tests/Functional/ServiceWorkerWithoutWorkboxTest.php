<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use function count;
use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerCompiler;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\WorkboxHelpers;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * The functions and constants called by the rules are declared by WorkboxHelpers, which writes nothing when
 * Workbox is disabled. A rule calling one of them anyway produces a service worker that throws a ReferenceError
 * when it is evaluated, and is never installed.
 *
 * The "workbox_disabled" environment of the test application turns Workbox off and every rule that has
 * something to write on.
 *
 * @see https://github.com/Spomky-Labs/pwa-bundle/issues/496
 *
 * @internal
 */
final class ServiceWorkerWithoutWorkboxTest extends KernelTestCase
{
    #[Test]
    public function theCompiledServiceWorkerCallsNoWorkboxHelper(): void
    {
        // Given
        self::bootKernel([
            'environment' => 'workbox_disabled',
        ]);
        $helpers = $this->workboxHelperNames();
        static::assertGreaterThan(10, count($helpers));

        // When
        $serviceWorker = $this->compileServiceWorker();

        // Then
        foreach ($helpers as $helper) {
            static::assertDoesNotMatchRegularExpression('/\b' . $helper . '\b/', $serviceWorker);
        }
        static::assertStringNotContainsString('workbox.', $serviceWorker);
    }

    #[Test]
    public function skipWaitingIsDeclaredWithPlainListeners(): void
    {
        // Given
        self::bootKernel([
            'environment' => 'workbox_disabled',
        ]);

        // When
        $serviceWorker = $this->compileServiceWorker();

        // Then
        static::assertStringContainsString(
            "self.addEventListener(\"install\", function (event) {\n  event.waitUntil(self.skipWaiting());\n});",
            $serviceWorker
        );
        static::assertStringContainsString(
            "self.addEventListener(\"activate\", function (event) {\n  event.waitUntil(self.clients.claim());\n});",
            $serviceWorker
        );
    }

    private function compileServiceWorker(): string
    {
        $builder = self::getContainer()->get(ServiceWorkerBuilder::class);
        static::assertInstanceOf(ServiceWorkerBuilder::class, $builder);
        $configuration = $builder->create();
        static::assertFalse($configuration->workbox->enabled);
        static::assertTrue($configuration->skipWaiting);
        static::assertTrue($configuration->workbox->clearCache);
        static::assertTrue($configuration->workbox->navigationPreload);
        static::assertTrue($configuration->workbox->backgroundFetch?->enabled);
        static::assertNotSame([], $configuration->workbox->backgroundSync);

        $compiler = self::getContainer()->get(ServiceWorkerCompiler::class);
        static::assertInstanceOf(ServiceWorkerCompiler::class, $compiler);
        foreach ($compiler->getFiles() as $file) {
            if ($file->url === '/sw.js') {
                return $file->getData();
            }
        }

        static::fail('The service worker was not compiled.');
    }

    /**
     * Every top-level function and constant that WorkboxHelpers declares when Workbox is enabled.
     *
     * @return list<string>
     */
    private function workboxHelperNames(): array
    {
        $workbox = new Workbox();
        $workbox->enabled = true;
        $serviceWorker = new ServiceWorker();
        $serviceWorker->workbox = $workbox;

        $denormalizer = static::createStub(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturn($serviceWorker);

        $declarations = (new WorkboxHelpers(new ServiceWorkerBuilder($denormalizer, [])))->process();
        preg_match_all('/^(?:async\s+)?(?:function|const)\s+(\w+)/m', $declarations, $matches);

        return $matches[1];
    }
}
