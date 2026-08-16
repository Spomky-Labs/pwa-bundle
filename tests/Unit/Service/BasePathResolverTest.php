<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
final class BasePathResolverTest extends TestCase
{
    #[Test]
    public function itReturnsAnEmptyBasePathWhenNoContextIsAvailable(): void
    {
        // Given
        $resolver = new BasePathResolver();

        // Then
        static::assertSame('', $resolver->getBasePath());
        static::assertSame('/sw.js', $resolver->prefix('/sw.js'));
    }

    #[Test]
    public function itUsesTheBasePathOfTheCurrentRequest(): void
    {
        // Given
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/MyProject/', server: [
            'SCRIPT_FILENAME' => '/MyProject/index.php',
            'SCRIPT_NAME' => '/MyProject/index.php',
            'PHP_SELF' => '/MyProject/index.php',
        ]));
        $resolver = new BasePathResolver(new RequestStackContext($requestStack));

        // Then
        static::assertSame('/MyProject', $resolver->getBasePath());
        static::assertSame('/MyProject/sw.js', $resolver->prefix('/sw.js'));
    }

    #[Test]
    public function itFallsBackToTheConfiguredBasePathWhenThereIsNoRequest(): void
    {
        // Given
        $resolver = new BasePathResolver(new RequestStackContext(new RequestStack(), '/MyProject'));

        // Then
        static::assertSame('/MyProject', $resolver->getBasePath());
        static::assertSame('/MyProject/sw.js', $resolver->prefix('/sw.js'));
    }

    #[Test]
    #[DataProvider('urlsAndExpectations')]
    public function itOnlyPrefixesRootRelativePaths(string $url, string $expected): void
    {
        // Given
        $resolver = new BasePathResolver(new RequestStackContext(new RequestStack(), '/MyProject'));

        // Then
        static::assertSame($expected, $resolver->prefix($url));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function urlsAndExpectations(): iterable
    {
        yield 'root relative path' => ['/favicon.ico', '/MyProject/favicon.ico'];
        yield 'root itself' => ['/', '/MyProject/'];
        yield 'relative path' => ['favicon.ico', 'favicon.ico'];
        yield 'empty path' => ['', ''];
        yield 'absolute URL' => [
            'https://storage.googleapis.com/workbox-cdn/releases/7.4.1/workbox-sw.js',
            'https://storage.googleapis.com/workbox-cdn/releases/7.4.1/workbox-sw.js',
        ];
        yield 'protocol relative URL' => ['//example.com/sw.js', '//example.com/sw.js'];
        yield 'data URI' => ['data:image/png;base64,AAAA', 'data:image/png;base64,AAAA'];
    }

    #[Test]
    public function itIgnoresTheTrailingSlashOfTheBasePath(): void
    {
        // Given
        $resolver = new BasePathResolver(new RequestStackContext(new RequestStack(), '/MyProject/'));

        // Then
        static::assertSame('/MyProject', $resolver->getBasePath());
        static::assertSame('/MyProject/sw.js', $resolver->prefix('/sw.js'));
    }
}
