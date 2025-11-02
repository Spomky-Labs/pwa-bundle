<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests;

use SpomkyLabs\PwaBundle\CachingStrategy\PreloadUrlsGeneratorInterface;
use SpomkyLabs\PwaBundle\Dto\Url;

/**
 * @internal
 */
class StaticPagesGenerator implements PreloadUrlsGeneratorInterface
{
    public function getAlias(): string
    {
        return 'static-pages';
    }

    public function generateUrls(): iterable
    {
        yield '/dummy/1';
        yield Url::create('/dummy/2');
    }
}
