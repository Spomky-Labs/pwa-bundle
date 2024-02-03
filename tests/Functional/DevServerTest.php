<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class DevServerTest extends WebTestCase
{
    #[Test]
    public static function theManifestIsServed(): void
    {
        // Given
        $client = static::createClient();

        // When
        $client->request('GET', '/site.webmanifest');

        // Then
        static::assertResponseIsSuccessful();
        static::assertResponseHeaderSame('Content-Type', 'application/manifest+json');
    }

    #[Test]
    public static function theServiceWorkerIsServed(): void
    {
        // Given
        $client = static::createClient();

        // When
        $client->request('GET', '/sw.js');

        // Then
        static::assertResponseIsSuccessful();
        static::assertResponseHeaderSame('Content-Type', 'application/javascript');
    }
}
