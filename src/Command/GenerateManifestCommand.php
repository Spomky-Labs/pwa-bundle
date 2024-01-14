<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use JsonException;
use SpomkyLabs\PwaBundle\Command\SectionProcessor\SectionProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Filesystem\Filesystem;
use function is_int;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsCommand(name: 'pwa:build', description: 'Generate the Progressive Web App Manifest')]
final class GenerateManifestCommand extends Command
{
    /**
     * @param iterable<SectionProcessor> $processors
     */
    public function __construct(
        #[TaggedIterator('pwa.section-processor')]
        private readonly iterable $processors,
        #[Autowire('%spomky_labs_pwa.config%')]
        private readonly array $config,
        #[Autowire('%spomky_labs_pwa.dest%')]
        private readonly array $dest,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Manifest Generator');
        $manifest = $this->config;
        $manifest = array_filter($manifest, static fn ($value) => ($value !== null && $value !== []));

        foreach ($this->processors as $processor) {
            $result = $processor->process($io, $this->config, $manifest);
            if (is_int($result)) {
                return $result;
            }
            $manifest = $result;
        }

        try {
            if (! $this->filesystem->exists(dirname($this->dest['manifest_filepath']))) {
                $this->filesystem->mkdir(dirname($this->dest['manifest_filepath']));
            }
            file_put_contents(
                (string) $this->dest['manifest_filepath'],
                json_encode(
                    $manifest,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                )
            );
        } catch (JsonException $exception) {
            $io->error(sprintf('Unable to generate the manifest file: %s', $exception->getMessage()));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
