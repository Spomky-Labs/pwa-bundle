<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class ServiceWorkerSectionProcessor implements SectionProcessor
{
    public function process(SymfonyStyle $io, array $config, array $manifest): array|int
    {
        if (! isset($manifest['serviceworker'])) {
            return $manifest;
        }
        unset($manifest['serviceworker']['generate'],$manifest['serviceworker']['filepath']);

        return $manifest;
    }
}
