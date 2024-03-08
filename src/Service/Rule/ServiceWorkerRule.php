<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

interface ServiceWorkerRule
{
    public function process(string $body): string;
}
