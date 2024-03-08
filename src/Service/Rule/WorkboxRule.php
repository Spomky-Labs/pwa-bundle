<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\Workbox;

interface WorkboxRule
{
    public function process(Workbox $workbox, string $body): string;
}
