<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

interface FileCompilerInterface
{
    /**
     * @return array<string>
     */
    public function supportedPublicUrls(): array;

    public function get(string $publicUrl): null|Data;
}
