<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Dto;

use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\ImageCache;

/**
 * @internal
 */
final class CacheTest extends TestCase
{
    #[Test]
    #[DataProvider('maxAges')]
    public function theMaxAgeIsConvertedToSeconds(int|string $maxAge, int $expectedSeconds): void
    {
        // Given
        $cache = new ImageCache();
        $cache->maxAge = $maxAge;

        // When
        $seconds = $cache->maxAgeInSeconds();

        // Then
        static::assertSame($expectedSeconds, $seconds);
    }

    #[Test]
    #[DataProvider('invalidMaxAges')]
    public function aMaxAgeThatIsNoDurationIsRejectedOnEveryPhpVersion(string $maxAge): void
    {
        // Given
        $cache = new ImageCache();
        $cache->maxAge = $maxAge;

        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid max age');

        // When
        $cache->maxAgeInSeconds();
    }

    /**
     * @return Iterator<string, array{int|string, int}>
     */
    public static function maxAges(): Iterator
    {
        yield 'seconds as an integer' => [3600, 3600];
        yield 'seconds as a relative format' => ['3600 seconds', 3600];
        yield 'minutes as a relative format' => ['90 minutes', 5400];
    }

    /**
     * @return Iterator<string, array{string}>
     */
    public static function invalidMaxAges(): Iterator
    {
        yield 'a word' => ['foo'];
        yield 'an empty string' => [''];
    }
}
