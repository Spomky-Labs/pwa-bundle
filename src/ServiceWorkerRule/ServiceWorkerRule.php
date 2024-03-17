<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

interface ServiceWorkerRule
{
    public function process(string $body): string;
}
