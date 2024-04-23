<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use function assert;

/**
 * @internal
 */
abstract class AbstractPwaTestCase extends KernelTestCase
{
    protected static Application $application;

    protected function setUp(): void
    {
        self::cleanupFolder();
        assert(self::$kernel !== null);
        self::$application = new Application(self::$kernel);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        self::cleanupFolder();
        parent::tearDown();
    }

    private static function cleanupFolder(): void
    {
        assert(self::$kernel !== null);
        $filesystem = self::getContainer()->get(Filesystem::class);
        assert($filesystem instanceof Filesystem);
        $filesystem->remove(sprintf('%s/samples', self::$kernel->getCacheDir()));
        $filesystem->remove(sprintf('%s/output', self::$kernel->getCacheDir()));
    }
}
