<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command\SectionProcessor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

final readonly class ActionsSectionProcessor implements SectionProcessor
{
    public function __construct(
        private ?RouterInterface $router = null,
    ) {
    }

    public function process(SymfonyStyle $io, array $config, array $manifest): array|int
    {
        if ($config['file_handlers'] === []) {
            return $manifest;
        }
        foreach ($manifest['file_handlers'] as $id => $handler) {
            if (str_starts_with((string) $handler['action'], '/')) {
                continue;
            }
            if ($this->router === null) {
                $io->error('The router is not available. Unable to generate the file handler action URL.');
                return Command::FAILURE;
            }
            $manifest['file_handlers'][$id]['action'] = $this->router->generate(
                $handler['action'],
                [],
                RouterInterface::RELATIVE_PATH
            );
        }

        return $manifest;
    }
}
