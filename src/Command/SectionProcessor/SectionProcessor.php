<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use Symfony\Component\Console\Style\SymfonyStyle;

interface SectionProcessor
{
    /**
     * @param array $config The configuration
     * @param array $manifest The manifest to update
     * @return array|int The updated manifest or an exit code in case of error
     */
    public function process(SymfonyStyle $io, array $config, array $manifest): array|int;
}
