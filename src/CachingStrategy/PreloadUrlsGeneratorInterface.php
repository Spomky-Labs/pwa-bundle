<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

use SpomkyLabs\PwaBundle\Dto\Url;

interface PreloadUrlsGeneratorInterface
{
    public function getAlias(): string;

    /**
     * @return iterable<Url|string>
     */
    public function generateUrls(): iterable;
}
