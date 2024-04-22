<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Event;

use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class NullEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        return $event;
    }
}
