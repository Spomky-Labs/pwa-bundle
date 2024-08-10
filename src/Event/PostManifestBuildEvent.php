<?php


namespace SpomkyLabs\PwaBundle\Event;

use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched during the ManifestBuilder::create method, after the configuration is denormalized
 */
class PostManifestBuildEvent extends Event
{
    public function __construct(private Manifest $manifest)
    {
    }

    public function setManifest(Manifest $manifest): void
    {
        $this->manifest = $manifest;
    }

    public function getManifest(): Manifest
    {
        return $this->manifest;
    }
}

