<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

use Facebook\WebDriver\WebDriverDimension;
use function function_exists;
use function is_array;
use function is_int;
use function is_scalar;
use RuntimeException;
use function sprintf;
use function strlen;
use Symfony\Component\Panther\Client;
use Throwable;

/**
 * Paints the document with a headless Chrome, driven by Panther.
 *
 * The browser is started on the first capture and reused for all the others: there are more than eighty
 * images to produce for a single application, and a browser start dwarfs the capture itself.
 */
final class ChromeHtmlRenderer implements HtmlRendererInterface
{
    private null|Client $client = null;

    public function __construct(
        private readonly null|Client $webClient = null,
    ) {
    }

    public function __destruct()
    {
        $this->close();
    }

    public function capture(string $html, int $width, int $height): string
    {
        $path = sprintf('%s/pwa-startup-%s.html', sys_get_temp_dir(), hash('xxh128', $html));
        if (file_put_contents($path, $html) === false) {
            throw new RuntimeException(sprintf('Unable to write the startup image document to "%s".', $path));
        }

        try {
            $client = $this->getClient();
            $client->request('GET', 'file://' . $path);
            $this->resizeViewport($client, $width, $height);
            $screenshot = $client->takeScreenshot();
        } catch (Throwable $exception) {
            // Panther and the WebDriver stack report a missing or mismatched driver from deep inside
            // themselves. The application asked for a template, so it is told what that costs.
            throw new RuntimeException(sprintf(
                'Unable to paint a startup image with a headless browser: %s. A Chrome driver matching the installed Chrome is needed to render the startup image template; "vendor/bin/bdi detect drivers" installs one, and PANTHER_CHROME_DRIVER_BINARY points at an existing one.',
                $exception->getMessage()
            ), 0, $exception);
        } finally {
            @unlink($path);
        }

        $this->assertSize($screenshot, $width, $height);

        return $screenshot;
    }

    public function close(): void
    {
        if ($this->client === null) {
            return;
        }
        try {
            $this->client->quit();
        } catch (Throwable) {
            // The browser is already gone, which is precisely what was being asked for.
        }
        $this->client = null;
    }

    /**
     * The window is sized, then the viewport it actually yields is measured and the difference given back.
     * Chrome sizes the *window*, and whatever it paints around the page — a scrollbar, a headless shell
     * border — is taken out of the page's share of it.
     */
    private function resizeViewport(Client $client, int $width, int $height): void
    {
        $window = $client->manage()
            ->window();
        $window->setSize(new WebDriverDimension($width, $height));

        $inner = $client->executeScript('return [window.innerWidth, window.innerHeight];');
        if (! is_array($inner) || ! isset($inner[0], $inner[1]) || ! is_numeric($inner[0]) || ! is_numeric(
            $inner[1]
        )) {
            return;
        }
        $horizontalLoss = $width - (int) $inner[0];
        $verticalLoss = $height - (int) $inner[1];
        if ($horizontalLoss === 0 && $verticalLoss === 0) {
            return;
        }

        $window->setSize(new WebDriverDimension($width + $horizontalLoss, $height + $verticalLoss));
    }

    /**
     * Reads the dimensions straight out of the PNG header, so that a browser that could not honour the
     * requested window size is reported here rather than by iOS silently dropping the image.
     */
    private function assertSize(string $screenshot, int $width, int $height): void
    {
        if (strlen($screenshot) < 24 || ! str_starts_with($screenshot, "\x89PNG\r\n\x1a\n")) {
            throw new RuntimeException('The browser did not return a PNG screenshot.');
        }
        /** @var array{width: int, height: int} $header */
        $header = unpack('Nwidth/Nheight', substr($screenshot, 16, 8));
        if ($header['width'] === $width && $header['height'] === $height) {
            return;
        }

        throw new RuntimeException(sprintf(
            'The browser produced a %dx%d startup image where %dx%d was requested. iOS only shows an image whose dimensions match the screen exactly. Make sure the browser runs headless, so that the window is not capped by the display.',
            $header['width'],
            $header['height'],
            $width,
            $height,
        ));
    }

    private function getClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }
        if ($this->webClient !== null) {
            return $this->client = clone $this->webClient;
        }

        $driver = $this->getEnv('PANTHER_CHROME_DRIVER_BINARY');

        return $this->client = Client::createChromeClient(
            $driver,
            $this->getArguments(),
            [
                'port' => $this->getAvailablePort(),
                'capabilities' => [
                    'acceptInsecureCerts' => true,
                ],
            ]
        );
    }

    /**
     * @return list<string>
     */
    private function getArguments(): array
    {
        $arguments = [];
        if ($this->getEnv('PANTHER_NO_HEADLESS') === null) {
            $arguments[] = '--headless';
            $arguments[] = '--disable-gpu';
        }
        if ($this->getEnv('PANTHER_NO_SANDBOX') !== null || $this->getEnv('HAS_JOSH_K_SEAL_OF_APPROVAL') !== null) {
            $arguments[] = '--no-sandbox';
        }
        // A scrollbar would eat into the viewport, and show up on the image itself.
        $arguments[] = '--hide-scrollbars';
        // The document is written to the temporary directory and loaded over file://, so that no web
        // server is needed to compile the images.
        $arguments[] = '--allow-file-access-from-files';

        $extra = $this->getEnv('PANTHER_CHROME_ARGUMENTS');
        if ($extra !== null) {
            $arguments = [...$arguments, ...explode(' ', $extra)];
        }

        return $arguments;
    }

    private function getEnv(string $name): null|string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? null;
        if ($value === null || $value === false || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private function getAvailablePort(): int
    {
        if (! function_exists('socket_create_listen')) {
            return random_int(9000, 9999);
        }

        $socket = socket_create_listen(0);
        if ($socket === false) {
            return random_int(9000, 9999);
        }
        $port = null;
        socket_getsockname($socket, $address, $port);
        socket_close($socket);

        return is_int($port) ? $port : random_int(9000, 9999);
    }
}
