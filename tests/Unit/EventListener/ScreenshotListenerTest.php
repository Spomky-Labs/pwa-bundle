<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SpomkyLabs\PwaBundle\EventListener\ScreenshotListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * @internal
 */
final class ScreenshotListenerTest extends TestCase
{
    #[Test]
    public function itDoesNotDisableProfilerWhenNotMainRequest(): void
    {
        // Given
        $profiler = $this->createMock(Profiler::class);
        $profiler->expects(static::never())
            ->method('disable');

        $listener = new ScreenshotListener($profiler, 'HeadlessChrome');

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('user-agent', 'HeadlessChrome/1.0');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        // When
        $listener->onRequest($event);

        // Then - expectations are checked automatically
    }

    #[Test]
    public function itDoesNotDisableProfilerWhenNoProfiler(): void
    {
        // Given
        $listener = new ScreenshotListener(null, 'HeadlessChrome');

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('user-agent', 'HeadlessChrome/1.0');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // When
        $listener->onRequest($event);

        // Then - should not throw exception
        static::assertTrue(true); // No exception means test passed
    }

    #[Test]
    public function itDoesNotDisableProfilerWhenNoUserAgent(): void
    {
        // Given
        $profiler = $this->createMock(Profiler::class);
        $profiler->expects(static::never())
            ->method('disable');

        $listener = new ScreenshotListener($profiler, 'HeadlessChrome');

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // When
        $listener->onRequest($event);

        // Then - expectations are checked automatically
    }

    #[Test]
    public function itDoesNotDisableProfilerWhenUserAgentDoesNotMatch(): void
    {
        // Given
        $profiler = $this->createMock(Profiler::class);
        $profiler->expects(static::never())
            ->method('disable');

        $listener = new ScreenshotListener($profiler, 'HeadlessChrome');

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('user-agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // When
        $listener->onRequest($event);

        // Then - expectations are checked automatically
    }

    #[Test]
    public function itDisablesProfilerWhenUserAgentMatches(): void
    {
        // Given
        $profiler = $this->createMock(Profiler::class);
        $profiler->expects(static::once())
            ->method('disable');

        $listener = new ScreenshotListener($profiler, 'HeadlessChrome');

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('user-agent', 'Mozilla/5.0 (X11; Linux x86_64) HeadlessChrome/1.0');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // When
        $listener->onRequest($event);

        // Then - expectations are checked automatically
    }

    #[Test]
    public function itUsesDefaultUserAgentWhenNotProvided(): void
    {
        // Given
        $profiler = $this->createMock(Profiler::class);
        $profiler->expects(static::once())
            ->method('disable');

        $listener = new ScreenshotListener($profiler);

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('user-agent', 'PWAScreenshotBot/1.0');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // When
        $listener->onRequest($event);

        // Then - expectations are checked automatically
    }

    #[Test]
    public function itCanSetLogger(): void
    {
        // Given
        $listener = new ScreenshotListener(null, 'HeadlessChrome');
        $logger = static::createStub(LoggerInterface::class);

        // When
        $listener->setLogger($logger);

        // Then
        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('user-agent', 'HeadlessChrome/1.0');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should not throw exception
        $listener->onRequest($event);
        static::assertTrue(true); // No exception means test passed
    }

    #[Test]
    public function itLogsDebugMessages(): void
    {
        // Given
        $listener = new ScreenshotListener(static::createStub(Profiler::class), 'HeadlessChrome');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::atLeastOnce())
            ->method('debug');

        $listener->setLogger($logger);

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('user-agent', 'HeadlessChrome/1.0');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // When
        $listener->onRequest($event);

        // Then - expectations are checked automatically
    }
}
