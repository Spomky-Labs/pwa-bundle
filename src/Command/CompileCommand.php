<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\Service\FileCompiler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pwa:compile', description: 'Compile the PWA assets.')]
final class CompileCommand extends Command
{
    public function __construct(
        private readonly FileCompiler $compiler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'context-only',
            null,
            InputOption::VALUE_NONE,
            'Compile only assets that depend on the application context (e.g. the manifest).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $contextOnly = (bool) $input->getOption('context-only');

        $io->title('Compiling the PWA assets');
        $this->compiler->compile($io, $contextOnly);
        $io->success('The PWA assets have been compiled.');

        return self::SUCCESS;
    }
}
