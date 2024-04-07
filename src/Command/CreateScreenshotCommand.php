<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Command;

use Facebook\WebDriver\WebDriverDimension;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Panther\Client;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use function assert;
use function count;

#[AsCommand(
    name: 'pwa:create:screenshot',
    description: 'Take a screenshot of the application store it in your asset folder'
)]
final class CreateScreenshotCommand extends Command
{
    public function __construct(
        private readonly AssetMapperInterface $assetMapper,
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly null|ImageProcessorInterface $imageProcessor,
        #[Autowire('@pwa.web_client')]
        private readonly null|Client $webClient = null,
        #[Autowire(param: 'spomky_labs_pwa.screenshot_user_agent')]
        private readonly null|string $userAgent = null,
    ) {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->imageProcessor !== null;
    }

    protected function configure(): void
    {
        $this->addArgument(
            'url',
            InputArgument::REQUIRED,
            'The URL to take a screenshot from',
            null,
            ['https://example.com', 'https://example.com/feature1']
        );
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_OPTIONAL,
            'The output directory',
            sprintf('%s/assets/screenshots/', $this->projectDir)
        );
        $this->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'The output filename', 'screenshot');
        $this->addOption('width', null, InputOption::VALUE_OPTIONAL, 'The width of the screenshot');
        $this->addOption('height', null, InputOption::VALUE_OPTIONAL, 'The height of the screenshot');
        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_OPTIONAL,
            'The format of the screenshots',
            null,
            ['png', 'jpg', 'webp']
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PWA - Take a screenshot');
        if ($this->imageProcessor === null) {
            $io->error('The image processor is not enabled.');
            return self::FAILURE;
        }

        $url = $input->getArgument('url');
        $dest = rtrim((string) $input->getOption('output'), '/');
        $height = $input->getOption('height');
        $width = $input->getOption('width');
        $format = $input->getOption('format');

        $client = $this->getClient();
        $crawler = $client->request('GET', $url);

        $tmpName = $this->filesystem
            ->tempnam('', 'pwa-');
        if ($width !== null && $height !== null) {
            if ($width < 0 || $height < 0) {
                $io->error('Width and height must be positive integers.');
                return self::FAILURE;
            }
            $client->manage()
                ->window()
                ->setSize(new WebDriverDimension((int) $width, (int) $height));
        }
        $client->manage()
            ->window()
            ->fullscreen();
        $client->takeScreenshot($tmpName);
        try {
            $client->waitFor('title', 5, 500);
            $result = preg_match("/<title>(.+)<\/title>/i", $crawler->html(), $title);
            $title = $result === 1 ? $title[1] : null;
        } catch (Throwable) {
            $title = null;
        }

        if ($format !== null) {
            $data = $this->imageProcessor->process(file_get_contents($tmpName), null, null, $format);
            file_put_contents($tmpName, $data);
        }
        if ($width === null || $height === null) {
            ['width' => $width, 'height' => $height] = $this->imageProcessor->getSizes(file_get_contents($tmpName));
        }

        $mime = MimeTypes::getDefault();
        $mimeType = $mime->guessMimeType($tmpName);
        $extensions = $mime->getExtensions($mimeType);
        if (count($extensions) === 0) {
            $io->error(sprintf('Unable to guess the extension for the mime type "%s".', $mimeType));
            return self::FAILURE;
        }
        $sizes = '';
        if ($width !== null && $height !== null) {
            $sizes = sprintf('-%dx%d', (int) $width, (int) $height);
        }

        $format = current($extensions);
        $filename = sprintf('%s/%s%s.%s', $dest, $input->getOption('filename'), $sizes, $format);

        $this->filesystem->copy($tmpName, $filename, true);
        $this->filesystem->remove($tmpName);
        $asset = $this->assetMapper->getAssetFromSourcePath($filename);
        $outputMimeType = $mime->guessMimeType($filename);

        $config = [
            'src' => $asset === null ? $filename : $asset->logicalPath,
            'width' => (int) $width,
            'height' => (int) $height,
            'reference' => $url,
        ];
        if ($outputMimeType !== null) {
            $config['type'] = $outputMimeType;
        }
        if ($title !== null && $title !== '') {
            $config['label'] = $title;
        }
        $io->success('Screenshot saved. You can now use it in your application configuration file.');
        $io->writeln(Yaml::dump([
            'pwa' => [
                'manifest' => [
                    'screenshots' => [$config],
                ],
            ],
        ], 10, 2));

        return self::SUCCESS;
    }

    private function getAvailablePort(): int
    {
        $socket = socket_create_listen(0);
        assert($socket !== false, 'Unable to create a socket.');
        socket_getsockname($socket, $address, $port);
        socket_close($socket);

        return $port;
    }

    private function getDefaultArguments(): array
    {
        $args = [];

        if (! ($_SERVER['PANTHER_NO_HEADLESS'] ?? false)) {
            $args[] = '--headless';
            $args[] = '--window-size=1200,1100';
            $args[] = '--disable-gpu';
        }

        if ($_SERVER['PANTHER_DEVTOOLS'] ?? true) {
            $args[] = '--auto-open-devtools-for-tabs';
        }

        if ($_SERVER['PANTHER_NO_SANDBOX'] ?? $_SERVER['HAS_JOSH_K_SEAL_OF_APPROVAL'] ?? false) {
            $args[] = '--no-sandbox';
        }

        if ($_SERVER['PANTHER_CHROME_ARGUMENTS'] ?? false) {
            $arguments = explode(' ', (string) $_SERVER['PANTHER_CHROME_ARGUMENTS']);
            $args = array_merge($args, $arguments);
        }

        return $args;
    }

    private function getClient(): Client
    {
        if ($this->webClient !== null) {
            return clone $this->webClient;
        }
        $options = [
            'port' => $this->getAvailablePort(),
            'capabilities' => [
                'acceptInsecureCerts' => true,
            ],
        ];
        $arguments = $this->getDefaultArguments();
        if ($this->userAgent !== null) {
            $arguments[] = sprintf('--user-agent=%s', $this->userAgent);
        }

        return Client::createChromeClient(arguments: $arguments, options: $options);
    }
}
