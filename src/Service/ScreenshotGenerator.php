<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function assert;
use function count;
use Facebook\WebDriver\WebDriverDimension;
use function function_exists;
use function is_int;
use function is_string;
use SpomkyLabs\PwaBundle\Dto\ScreenshotConfiguration;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use function sprintf;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Panther\Client;
use Throwable;

final readonly class ScreenshotGenerator
{
    public function __construct(
        private AssetMapperInterface $assetMapper,
        private Filesystem $filesystem,
        private null|ImageProcessorInterface $imageProcessor,
        private ScreenshotAttributeCollector $attributeCollector,
        private ScreenshotUrlGenerator $urlGenerator,
        #[Autowire('@pwa.web_client')]
        private null|Client $webClient = null,
        #[Autowire(param: 'spomky_labs_pwa.screenshot_user_agent')]
        private null|string $userAgent = null,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->imageProcessor !== null;
    }

    public function generate(SymfonyStyle $io): bool
    {
        if ($this->imageProcessor === null) {
            $io->error('The image processor is not enabled.');
            return false;
        }

        // Load configurations from #[Screenshot] attributes
        ['configurations' => $configurations] = $this->attributeCollector->collect();
        if ($configurations === []) {
            $io->warning('No #[Screenshot] attributes found on controller methods.');
            return true; // Not an error, just nothing to do
        }

        // Validate all configurations
        foreach ($configurations as $index => $config) {
            $error = $config->validate();
            if ($error !== null) {
                $io->error(sprintf('Configuration #%d: %s', $index + 1, $error));
                return false;
            }
        }

        // Count total screenshots to generate
        $totalScreenshots = 0;
        foreach ($configurations as $config) {
            $totalScreenshots += count($config->sizes);
        }

        // Process all screenshots
        $results = [];
        $client = $this->getClient();
        $currentIndex = 0;

        foreach ($configurations as $configIndex => $config) {
            // Generate URL from config (either direct URL or route)
            $url = $this->urlGenerator->generateUrl($config);

            $io->section(sprintf(
                'URL %d/%d: %s (%d size(s))',
                $configIndex + 1,
                count($configurations),
                $url,
                count($config->sizes)
            ));

            foreach ($config->getExpandedSizes() as $sizeData) {
                $currentIndex++;
                $io->text(sprintf(
                    '  [%d/%d] %s...',
                    $currentIndex,
                    $totalScreenshots,
                    $sizeData['size']->getLabel()
                ));

                try {
                    $result = $this->processScreenshot(
                        $client,
                        $url,
                        $config,
                        $sizeData['width'],
                        $sizeData['height'],
                        $sizeData['formFactor']
                    );
                    $results[] = $result;
                    $io->success(sprintf('  Saved: %s', basename($result['filename'])));
                } catch (Throwable $e) {
                    $io->error(sprintf('  Failed: %s', $e->getMessage()));
                    if (! $io->confirm('Continue with remaining screenshots?')) {
                        return false;
                    }
                }
            }
        }

        // Display final summary
        $io->newLine();
        $io->success(sprintf('%d screenshot(s) generated successfully.', count($results)));

        return true;
    }

    /**
     * @return array{filename: string, config: array<string, mixed>}
     */
    private function processScreenshot(
        Client $client,
        string $url,
        ScreenshotConfiguration $config,
        int $width,
        int $height,
        string $formFactor
    ): array {
        // Ensure output directory exists
        if (! $this->filesystem->exists($config->output)) {
            $this->filesystem->mkdir($config->output);
        }

        assert($width >= 1);
        assert($height >= 1);
        $imageProcessor = $this->imageProcessor;
        assert($imageProcessor !== null);

        $tmpName = $this->filesystem->tempnam('', 'pwa-');

        try {
            $crawler = $client->request('GET', $url);

            // Set window size
            $client->manage()
                ->window()
                ->setSize(new WebDriverDimension($width, $height));

            // Force minimum height to match viewport to ensure consistent dimensions
            $client->executeScript(sprintf(
                'document.documentElement.style.minHeight = "%dpx"; document.body.style.minHeight = "%dpx";',
                $height,
                $height
            ));

            $client->takeScreenshot($tmpName);

            // Extract title from the page
            $title = null;
            try {
                $titleElement = $crawler->filter('title');
                if ($titleElement->count() > 0) {
                    $title = $titleElement->text();
                }
            } catch (Throwable) {
                // Title extraction failed, continue without it
            }

            // Process the screenshot - convert to target format
            $data = file_get_contents($tmpName);
            assert(is_string($data));
            $configuration = Configuration::create($width, $height, $config->format);
            $data = $imageProcessor->process($data, null, null, null, $configuration);
            file_put_contents($tmpName, $data);

            $filename = sprintf(
                '%s/%s-%dx%d.%s',
                $config->output,
                $config->filename,
                $width,
                $height,
                $config->format
            );

            $this->filesystem->copy($tmpName, $filename, true);
            $this->filesystem->remove($tmpName);
            $asset = $this->assetMapper->getAssetFromSourcePath($filename);
            $outputMimeType = MimeTypes::getDefault()->guessMimeType($filename);

            $resultConfig = [
                'src' => $asset === null ? $filename : $asset->logicalPath,
                'width' => $width,
                'height' => $height,
                'reference' => $url,
            ];
            if ($outputMimeType !== null) {
                $resultConfig['type'] = $outputMimeType;
            }
            if ($title !== null) {
                $resultConfig['label'] = $title;
            }
            $resultConfig['form_factor'] = $formFactor;
            if ($config->platform !== null) {
                $resultConfig['platform'] = $config->platform;
            }

            return [
                'filename' => $filename,
                'config' => $resultConfig,
            ];
        } catch (Throwable $e) {
            // Clean up temporary file on error
            if ($this->filesystem->exists($tmpName)) {
                $this->filesystem->remove($tmpName);
            }
            throw $e;
        }
    }

    private function getAvailablePort(): int
    {
        // If sockets extension is not available, use a random high port
        if (! function_exists('socket_create_listen')) {
            return random_int(9000, 9999);
        }

        $socket = socket_create_listen(0);
        assert($socket !== false, 'Unable to create a socket.');
        socket_getsockname($socket, $address, $port);
        socket_close($socket);
        assert(is_int($port), 'Unable to determine the socket port.');

        return $port;
    }

    /**
     * @return list<string>
     */
    private function getDefaultArguments(): array
    {
        $args = [];
        $headless = ! (bool) ($_SERVER['PANTHER_NO_HEADLESS'] ?? false);

        if ($headless) {
            $args[] = '--headless';
            $args[] = '--window-size=1200,1100';
            $args[] = '--disable-gpu';
        }

        // Only open devtools if not in headless mode
        if (! $headless && (bool) ($_SERVER['PANTHER_DEVTOOLS'] ?? false)) {
            $args[] = '--auto-open-devtools-for-tabs';
        }

        if ((bool) ($_SERVER['PANTHER_NO_SANDBOX'] ?? $_SERVER['HAS_JOSH_K_SEAL_OF_APPROVAL'] ?? false)) {
            $args[] = '--no-sandbox';
        }

        // Hide scrollbars in screenshots
        $args[] = '--hide-scrollbars';

        $chromeArguments = $_SERVER['PANTHER_CHROME_ARGUMENTS'] ?? null;
        if (is_string($chromeArguments) && $chromeArguments !== '') {
            $args = array_merge($args, explode(' ', $chromeArguments));
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

        // Allow specifying chromedriver binary via environment variable
        $chromeDriverBinary = $_SERVER['PANTHER_CHROME_DRIVER_BINARY'] ?? $_ENV['PANTHER_CHROME_DRIVER_BINARY'] ?? null;

        return Client::createChromeClient(
            is_string($chromeDriverBinary) ? $chromeDriverBinary : null,
            $arguments,
            $options
        );
    }
}
