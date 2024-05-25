<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use Psr\Log\LoggerInterface;

interface CanLogInterface
{
    public function setLogger(LoggerInterface $logger): void;
}
