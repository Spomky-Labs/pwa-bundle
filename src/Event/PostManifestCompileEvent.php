<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Event;

use SpomkyLabs\PwaBundle\Dto\Manifest;

final class PostManifestCompileEvent
{
    public function __construct(
        public Manifest $manifest,
        public string $data
    ) {
    }
}
