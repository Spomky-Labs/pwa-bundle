<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

interface FileCompilerInterface
{
    /**
     * @return iterable<Data>
     */
    public function getFiles(): iterable;
}
