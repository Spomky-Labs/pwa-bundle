<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

interface ServiceWorkerRuleInterface
{
    public function process(bool $debug = false): string;
}
