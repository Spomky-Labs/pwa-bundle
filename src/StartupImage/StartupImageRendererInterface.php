<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

interface StartupImageRendererInterface
{
    /**
     * The hash the generated file name is built on. Two definitions producing the very same bytes shall
     * hash alike, and any change to what shapes the rendering shall change it: the file is served as
     * immutable, so a stale hash is a stale image in every cache along the way.
     *
     * It is computed without rendering the image, so that the compiled file list can be built cheaply.
     */
    public function hash(StartupImageDefinition $definition): string;

    public function render(StartupImageDefinition $definition): string;
}
