<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use SpomkyLabs\PwaBundle\Service\FileCompiler;
use SpomkyLabs\PwaBundle\Service\ScreenshotGenerator;
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
        private readonly null|ScreenshotGenerator $screenshotGenerator = null,
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
        $this->addOption('no-screenshots', null, InputOption::VALUE_NONE, 'Skip screenshot generation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $noScreenshots = (bool) $input->getOption('no-screenshots');

        // Generate screenshots if the generator is available and not skipped
        if (! $noScreenshots && $this->screenshotGenerator?->isEnabled()) {
            $io->title('Generating screenshots');
            if (! $this->screenshotGenerator->generate($io)) {
                return self::FAILURE;
            }
        }

        $contextOnly = (bool) $input->getOption('context-only');

        $io->title('Compiling the PWA assets');
        $this->compiler->compile($io, $contextOnly);
        $io->success('The PWA assets have been compiled.');

        return self::SUCCESS;
    }
}
