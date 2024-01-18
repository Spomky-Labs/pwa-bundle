<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class ManifestFileDevServerTest extends WebTestCase
{
    #[Test]
    public static function aScreenshotIsCorrectlyTake(): void
    {
        // Given
        $client = static::createClient();

        // When
        $client->request('GET', '/site.webmanifest');

        // Then
        static::assertResponseIsSuccessful();
        static::assertResponseHeaderSame('Content-Type', 'application/manifest+json');
        dump($client->getResponse()->getContent());
    }
}
