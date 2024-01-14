<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use JsonException;
use SpomkyLabs\PwaBundle\Command\SectionProcessor\SectionProcessor;
use SpomkyLabs\PwaBundle\Dto\Configuration;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function dirname;
use function is_int;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsCommand(name: 'pwa:build', description: 'Generate the Progressive Web App Manifest')]
final class GenerateManifestCommand extends Command
{
    private readonly Configuration $configuration;

    private readonly Manifest $manifest;

    /**
     * @param iterable<SectionProcessor> $processors
     */
    public function __construct(
        #[TaggedIterator('pwa.section-processor')]
        private readonly iterable $processors,
        #[Autowire('%spomky_labs_pwa.config%')]
        private readonly array $config,
        #[Autowire('%spomky_labs_pwa.dest%')]
        array $dest,
        private readonly Filesystem $filesystem,
        private readonly DenormalizerInterface $serializer,
    ) {
        parent::__construct();
        $this->configuration = $this->serializer->denormalize($dest, Configuration::class);
        $this->manifest = $this->serializer->denormalize($config, Manifest::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA Manifest Generator');
        $manifest = $this->config;
        $manifest = array_filter($manifest, static fn ($value) => ($value !== null && $value !== []));

        dump($this->configuration, $this->manifest);

        foreach ($this->processors as $processor) {
            $result = $processor->process($io, $this->config, $manifest);
            if (is_int($result)) {
                return $result;
            }
            $manifest = $result;
        }

        try {
            if (! $this->filesystem->exists(dirname($this->configuration->manifestFilepath))) {
                $this->filesystem->mkdir(dirname($this->configuration->manifestFilepath));
            }
            file_put_contents(
                $this->configuration->manifestFilepath,
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
