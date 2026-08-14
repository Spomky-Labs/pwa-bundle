<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use function extension_loaded;
use function in_array;
use InvalidArgumentException;
use function is_bool;
use function is_string;
use function sprintf;
use function strlen;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'pwa:create:config',
    description: 'Create the configuration file of your Progressive Web App'
)]
final class CreateConfigCommand extends Command
{
    /**
     * The recommended sizes for the application icons.
     *
     * @var array<int, int>
     */
    private const ICON_SIZES = [48, 72, 96, 128, 144, 168, 192, 256, 512];

    /**
     * @var array<int, string>
     */
    private const DISPLAY_MODES = ['fullscreen', 'standalone', 'minimal-ui', 'browser'];

    /**
     * The built-in image processors, in order of preference. Imagick comes first as it is
     * the only one able to read SVG sources.
     *
     * @var array<int, string>
     */
    private const IMAGE_PROCESSORS = ['imagick', 'gd'];

    private const NO_IMAGE_PROCESSOR = 'none';

    private const DEFAULT_CONFIG_PATH = 'config/packages/pwa.yaml';

    private const DEFAULT_SERVICE_WORKER_SRC = 'sw.js';

    private const SHORT_NAME_MAX_LENGTH = 12;

    private const DOCUMENTATION_URL = 'https://pwa.spomky-labs.com/';

    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command creates the configuration file of the bundle.

                    Run it without any option to be guided step by step:

                      <info>php %command.full_name%</info>

                    Every value can also be passed as an option. Combined with <comment>--no-interaction</comment>,
                    the command never asks anything, which is handy for scripts and recipes:

                      <info>php %command.full_name% --no-interaction --name="HackerNews PWA" --description="A HackerNews implementation based on Symfony"</info>

                    Use <comment>--dry-run</comment> to print the configuration instead of writing it:

                      <info>php %command.full_name% --no-interaction --dry-run > config/packages/pwa.yaml</info>
                    HELP
            )
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the application', null, [
                'My awesome application',
            ])
            ->addOption(
                'short-name',
                null,
                InputOption::VALUE_REQUIRED,
                'The short name of the application, displayed under the icon',
                null,
                ['Awesome']
            )
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'The description of the application')
            ->addOption('start-url', null, InputOption::VALUE_REQUIRED, 'The URL loaded when the application starts', null, [
                '/',
            ])
            ->addOption(
                'display',
                null,
                InputOption::VALUE_REQUIRED,
                sprintf('The display mode of the application (%s)', implode(', ', self::DISPLAY_MODES)),
                null,
                self::DISPLAY_MODES
            )
            ->addOption('theme-color', null, InputOption::VALUE_REQUIRED, 'The theme color of the application', null, [
                '#ffffff',
            ])
            ->addOption(
                'background-color',
                null,
                InputOption::VALUE_REQUIRED,
                'The background color of the application',
                null,
                ['#ffffff']
            )
            ->addOption(
                'icon',
                null,
                InputOption::VALUE_REQUIRED,
                'The source image used to generate the icons and the favicons. Can be served by Asset Mapper.',
                null,
                ['images/logo.svg']
            )
            ->addOption('favicons', null, InputOption::VALUE_NEGATABLE, 'Whether the favicons are generated')
            ->addOption('serviceworker', null, InputOption::VALUE_NEGATABLE, 'Whether the service worker is enabled')
            ->addOption(
                'serviceworker-src',
                null,
                InputOption::VALUE_REQUIRED,
                'The path to the service worker source file. Can be served by Asset Mapper.',
                null,
                [self::DEFAULT_SERVICE_WORKER_SRC]
            )
            ->addOption(
                'image-processor',
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'The image processor used to generate the icons (%s)',
                    implode(', ', [...self::IMAGE_PROCESSORS, self::NO_IMAGE_PROCESSOR])
                ),
                null,
                [...self::IMAGE_PROCESSORS, self::NO_IMAGE_PROCESSOR]
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'The configuration file to write, relative to the project directory when not absolute',
                null,
                [self::DEFAULT_CONFIG_PATH]
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite the configuration file if it already exists')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Display the configuration instead of writing it')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getOption('dry-run') === true) {
            // The generated configuration is the only thing written on the standard output so
            // that it can be redirected to a file.
            return;
        }
        (new SymfonyStyle($input, $output))->title('PWA Configuration Generator');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->text([
            'This command creates the configuration file of the bundle.',
            'Press <comment>Enter</comment> to accept the proposed value.',
        ]);
        $io->newLine();

        if ($this->getStringOption($input, 'name') === null) {
            $input->setOption(
                'name',
                $io->ask('Name of the application', $this->guessApplicationName(), self::notEmpty(...))
            );
        }
        $name = $this->getStringOption($input, 'name') ?? $this->guessApplicationName();

        if ($this->getStringOption($input, 'short-name') === null) {
            $input->setOption(
                'short-name',
                $io->ask('Short name, displayed under the icon', self::shortenName($name), self::notEmpty(...))
            );
        }
        if ($this->getStringOption($input, 'description') === null) {
            $input->setOption('description', $io->ask('Description of the application (optional)'));
        }
        if ($this->getStringOption($input, 'start-url') === null) {
            $input->setOption('start-url', $io->ask('URL loaded when the application starts', '/', self::notEmpty(...)));
        }
        if ($this->getStringOption($input, 'display') === null) {
            $input->setOption('display', $io->choice('Display mode', self::DISPLAY_MODES, 'standalone'));
        }
        if ($this->getStringOption($input, 'theme-color') === null) {
            $input->setOption('theme-color', $io->ask('Theme color', '#ffffff', self::notEmpty(...)));
        }
        if ($this->getStringOption($input, 'background-color') === null) {
            $input->setOption('background-color', $io->ask('Background color', '#ffffff', self::notEmpty(...)));
        }

        if ($this->getStringOption($input, 'icon') === null) {
            $io->newLine();
            $io->text(
                'The icons and the favicons are generated from a single source image. It can be an Asset Mapper logical path (e.g. <comment>images/logo.svg</comment>) or a path to a file.'
            );
            $input->setOption('icon', $io->ask('Source image of the application (leave empty to skip)'));
        }
        $hasIcon = $this->getStringOption($input, 'icon') !== null;
        if ($hasIcon && $this->getBoolOption($input, 'favicons') === null) {
            $input->setOption('favicons', $io->confirm('Generate the favicons from this image?'));
        }

        if ($this->getBoolOption($input, 'serviceworker') === null) {
            $io->newLine();
            $input->setOption(
                'serviceworker',
                $io->confirm('Enable the service worker (offline support, caching, push notifications)?')
            );
        }
        $hasServiceWorker = $this->getBoolOption($input, 'serviceworker') === true;
        if ($hasServiceWorker && $this->getStringOption($input, 'serviceworker-src') === null) {
            $input->setOption('serviceworker-src', $io->ask(
                'Path to the service worker source file',
                self::DEFAULT_SERVICE_WORKER_SRC,
                self::notEmpty(...)
            ));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $this->getStringOption($input, 'name') ?? $this->guessApplicationName();
        $display = $this->getStringOption($input, 'display') ?? 'standalone';
        if (! in_array($display, self::DISPLAY_MODES, true)) {
            $io->error(sprintf(
                'The display mode "%s" is not supported. Expected one of: %s.',
                $display,
                implode(', ', self::DISPLAY_MODES)
            ));
            return self::INVALID;
        }

        $icon = $this->getStringOption($input, 'icon');
        $favicons = $this->getBoolOption($input, 'favicons') ?? ($icon !== null);
        if ($favicons && $icon === null) {
            $io->error(
                'The favicons are generated from a source image. Please set the "--icon" option or disable them with "--no-favicons".'
            );
            return self::INVALID;
        }

        $requestedImageProcessor = $this->getStringOption($input, 'image-processor');
        if ($requestedImageProcessor !== null
            && in_array($requestedImageProcessor, self::IMAGE_PROCESSORS, true)
            && ! extension_loaded($requestedImageProcessor)
        ) {
            $io->error(sprintf(
                'The image processor "%s" requires the "%s" PHP extension, which is not loaded.',
                $requestedImageProcessor,
                $requestedImageProcessor
            ));
            return self::INVALID;
        }
        $imageProcessor = $this->resolveImageProcessor($requestedImageProcessor, $io, $icon !== null);

        $serviceWorker = $this->getBoolOption($input, 'serviceworker') ?? true;
        $serviceWorkerSrc = $this->getStringOption($input, 'serviceworker-src') ?? self::DEFAULT_SERVICE_WORKER_SRC;

        $configuration = $this->buildConfiguration(
            $name,
            $this->getStringOption($input, 'short-name') ?? self::shortenName($name),
            $this->getStringOption($input, 'description'),
            $this->getStringOption($input, 'start-url') ?? '/',
            $display,
            $this->getStringOption($input, 'theme-color') ?? '#ffffff',
            $this->getStringOption($input, 'background-color') ?? '#ffffff',
            $icon,
            $favicons,
            $serviceWorker,
            $serviceWorkerSrc,
            $imageProcessor,
        );
        $contents = sprintf(
            "# See %s for the complete list of options.\n%s",
            self::DOCUMENTATION_URL,
            Yaml::dump($configuration, 5, 4)
        );

        if ($input->getOption('dry-run') === true) {
            $output->write($contents);
            return self::SUCCESS;
        }

        $path = $this->resolvePath($this->getStringOption($input, 'path') ?? self::DEFAULT_CONFIG_PATH);
        if ($this->filesystem->exists($path) && $input->getOption('force') !== true) {
            $overwrite = $input->isInteractive() && $io->confirm(
                sprintf('The file "%s" already exists. Overwrite it?', $this->relativizePath($path)),
                false
            );
            if (! $overwrite) {
                $io->error(sprintf(
                    'The file "%s" already exists. Use the "--force" option to overwrite it.',
                    $this->relativizePath($path)
                ));
                return self::FAILURE;
            }
        }

        try {
            $this->filesystem->dumpFile($path, $contents);
        } catch (IOException $exception) {
            $io->error(sprintf('Unable to write the file "%s": %s', $this->relativizePath($path), $exception->getMessage()));
            return self::FAILURE;
        }
        $io->success(sprintf('The configuration file "%s" has been created.', $this->relativizePath($path)));

        if ($serviceWorker) {
            $created = $this->createServiceWorkerFile($serviceWorkerSrc);
            if ($created !== null) {
                $io->text(sprintf(
                    'The empty service worker file "%s" has been created. It will be populated by the bundle.',
                    $this->relativizePath($created)
                ));
                $io->newLine();
            }
        }

        $nextSteps = [];
        $nextSteps[] = 'Call the <comment>pwa()</comment> Twig function at the end of the head section of your templates.';
        if ($icon !== null) {
            $nextSteps[] = 'Check the generated icons with <comment>bin/console pwa:compile</comment>.';
        }
        $nextSteps[] = sprintf('Discover all the available options on %s.', self::DOCUMENTATION_URL);

        $io->section('Next steps');
        $io->listing($nextSteps);

        return self::SUCCESS;
    }

    /**
     * @return array{pwa: array<string, mixed>}
     */
    private function buildConfiguration(
        string $name,
        string $shortName,
        null|string $description,
        string $startUrl,
        string $display,
        string $themeColor,
        string $backgroundColor,
        null|string $icon,
        bool $favicons,
        bool $serviceWorker,
        string $serviceWorkerSrc,
        null|string $imageProcessor,
    ): array {
        $configuration = [];
        if ($imageProcessor !== null) {
            $configuration['image_processor'] = $imageProcessor;
        }

        $manifest = [
            'enabled' => true,
            'name' => $name,
            'short_name' => $shortName,
        ];
        if ($description !== null) {
            $manifest['description'] = $description;
        }
        $manifest['start_url'] = $startUrl;
        $manifest['display'] = $display;
        $manifest['theme_color'] = $themeColor;
        $manifest['background_color'] = $backgroundColor;
        if ($icon !== null) {
            $manifest['icons'] = [
                [
                    'src' => $icon,
                    'sizes' => self::ICON_SIZES,
                    'format' => 'png',
                ],
            ];
        }
        $configuration['manifest'] = $manifest;

        if ($favicons && $icon !== null) {
            $configuration['favicons'] = [
                'enabled' => true,
                'default' => [
                    'src' => $icon,
                    'background_color' => $backgroundColor,
                ],
            ];
        }

        if ($serviceWorker) {
            $configuration['serviceworker'] = [
                'enabled' => true,
                'src' => $serviceWorkerSrc,
            ];
        }

        return [
            'pwa' => $configuration,
        ];
    }

    private function resolveImageProcessor(
        null|string $requested,
        SymfonyStyle $io,
        bool $iconsAreGenerated
    ): null|string {
        if ($requested === self::NO_IMAGE_PROCESSOR) {
            return null;
        }
        if ($requested !== null) {
            return $requested;
        }
        if (! $iconsAreGenerated) {
            return null;
        }
        foreach (self::IMAGE_PROCESSORS as $imageProcessor) {
            if (extension_loaded($imageProcessor)) {
                return $imageProcessor;
            }
        }

        $io->getErrorStyle()
            ->warning(
                'Neither the "imagick" nor the "gd" PHP extension is loaded: the icons and the favicons cannot be generated. Install one of them, then set the "pwa.image_processor" option.'
            );

        return null;
    }

    /**
     * The service worker source file has to exist for Asset Mapper to serve it. An empty file is
     * enough: the bundle populates it with the configured rules.
     */
    private function createServiceWorkerFile(string $serviceWorkerSrc): null|string
    {
        if (Path::isAbsolute($serviceWorkerSrc) || str_contains($serviceWorkerSrc, '..')) {
            return null;
        }
        $path = Path::join($this->projectDir, 'assets', $serviceWorkerSrc);
        if ($this->filesystem->exists($path)) {
            return null;
        }

        try {
            $this->filesystem->dumpFile($path, '');
        } catch (IOException) {
            return null;
        }

        return $path;
    }

    private function resolvePath(string $path): string
    {
        return Path::isAbsolute($path) ? Path::canonicalize($path) : Path::join($this->projectDir, $path);
    }

    private function relativizePath(string $path): string
    {
        if (! str_starts_with($path, $this->projectDir . '/')) {
            return $path;
        }

        return substr($path, strlen($this->projectDir) + 1);
    }

    private function guessApplicationName(): string
    {
        $name = trim(str_replace(['-', '_', '.'], ' ', basename($this->projectDir)));
        if ($name === '') {
            return 'My Application';
        }

        return ucwords($name);
    }

    /**
     * The short name is displayed under the icon on the home screen and is truncated by the
     * platforms when it is too long.
     */
    private static function shortenName(string $name): string
    {
        if (mb_strlen($name) <= self::SHORT_NAME_MAX_LENGTH) {
            return $name;
        }
        $words = preg_split('/\s+/', $name);
        $shortName = '';
        foreach ($words === false ? [] : $words as $word) {
            $candidate = $shortName === '' ? $word : $shortName . ' ' . $word;
            if ($shortName !== '' && mb_strlen($candidate) > self::SHORT_NAME_MAX_LENGTH) {
                break;
            }
            $shortName = $candidate;
        }

        return mb_substr($shortName, 0, self::SHORT_NAME_MAX_LENGTH);
    }

    private static function notEmpty(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            throw new InvalidArgumentException('This value cannot be empty.');
        }

        return $value;
    }

    private function getStringOption(InputInterface $input, string $name): null|string
    {
        $value = $input->getOption($name);
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function getBoolOption(InputInterface $input, string $name): null|bool
    {
        $value = $input->getOption($name);

        return is_bool($value) ? $value : null;
    }
}
