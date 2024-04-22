<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Event;

use SpomkyLabs\PwaBundle\Dto\Manifest;

class PreManifestCompileEvent
{
    public function __construct(
        public Manifest $manifest,
    ) {
    }
}
