<?php


namespace SpomkyLabs\PwaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched during the ManifestBuilder::create method, before the configuration is denormalized
 */
class PreManifestBuildEvent extends Event
{
    public function __construct(private array $config)
    {
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
