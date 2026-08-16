<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use function array_key_exists;
use function assert;
use function function_exists;
use GdImage;
use function implode;
use InvalidArgumentException;
use function is_string;
use function mb_strlen;
use RuntimeException;
use function sprintf;
use Throwable;

final readonly class GDImageProcessor implements ImageProcessorInterface
{
    use ConfigurationTrait;

    /**
     * The supported formats, mapped to the GD function that writes them. The extension can be built without any of
     * the last three, so the presence of the writer is what the format is checked against.
     *
     * @var array<string, string>
     */
    private const ENCODERS = [
        'png' => 'imagepng',
        'jpeg' => 'imagejpeg',
        'gif' => 'imagegif',
        'ico' => 'imagepng',
        'bmp' => 'imagebmp',
        'webp' => 'imagewebp',
        'avif' => 'imageavif',
    ];

    public function process(
        string $image,
        ?int $width,
        ?int $height,
        ?string $format,
        null|Configuration $configuration = null
    ): string {
        $configuration = $this->getConfiguration($image, $width, $height, $format, $configuration);
        $encodedFormat = $this->supportedFormat($configuration->format);
        $mainImage = $this->createMainImage($image, $configuration);
        $background = $this->createBackground($configuration);
        imagecopy($background, $mainImage, 0, 0, 0, 0, $configuration->width, $configuration->height);

        return $this->capture(function () use ($background, $encodedFormat, $configuration): void {
            switch ($encodedFormat) {
                case 'png':
                    imagesavealpha($background, true);
                    imagepng($background);
                    break;
                case 'jpeg':
                    imagejpeg($background, null, ImageFormat::QUALITY);
                    break;
                case 'gif':
                    imagegif($background);
                    break;
                case 'bmp':
                    // Uncompressed, as ImageMagick writes it: potrace reads the result of the silhouette pass.
                    imagebmp($background, null, false);
                    break;
                case 'webp':
                    imagesavealpha($background, true);
                    imagewebp($background, null, ImageFormat::QUALITY);
                    break;
                case 'avif':
                    imagesavealpha($background, true);
                    imageavif($background, null, ImageFormat::QUALITY);
                    break;
                case 'ico':
                    $this->writeIco($background, $configuration);
                    break;
            }
        });
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getSizes(string $image): array
    {
        $source = $this->read($image);

        return [
            'width' => imagesx($source),
            'height' => imagesy($source),
        ];
    }

    /**
     * A single image ICO, holding the PNG payload every browser still in use reads.
     */
    private function writeIco(GdImage $image, Configuration $configuration): void
    {
        $pngData = $this->capture(static function () use ($image): void {
            imagesavealpha($image, true);
            imagepng($image);
        });

        // @phpstan-ignore-next-line
        echo pack('v3', 0, 1, 1);
        // @phpstan-ignore-next-line
        echo pack(
            'C4v2V2',
            $configuration->width,
            $configuration->height,
            0,
            0,
            1,
            32,
            mb_strlen($pngData, '8bit'),
            22
        );
        // @phpstan-ignore-next-line
        echo $pngData;
    }

    private function capture(callable $writer): string
    {
        ob_start();
        try {
            $writer();
        } catch (Throwable $throwable) {
            ob_end_clean();
            throw $throwable;
        }
        $result = ob_get_clean();
        assert(is_string($result));

        return $result;
    }

    private function supportedFormat(string $format): string
    {
        $normalized = ImageFormat::normalize($format);
        if (! array_key_exists($normalized, self::ENCODERS)) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" format is not supported by the GD image processor. Supported formats are: %s.',
                $format,
                implode(', ', array_keys(self::ENCODERS))
            ));
        }
        if (! function_exists(self::ENCODERS[$normalized])) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" format requires the GD extension to provide %s().',
                $format,
                self::ENCODERS[$normalized]
            ));
        }

        return $normalized;
    }

    /**
     * The GD warning is silenced because the failure is reported as an exception instead: it used to reach the output
     * of the console command, then the assertion that followed turned into a TypeError in production, where
     * assertions are compiled out.
     */
    private function read(string $image): GdImage
    {
        $source = @imagecreatefromstring($image);
        if ($source !== false) {
            return $source;
        }
        if ($this->isSvg($image)) {
            throw new InvalidArgumentException(
                'The GD image processor cannot read SVG images. Use the Imagick image processor, or point the configuration at a raster source.'
            );
        }

        throw new InvalidArgumentException(
            'The image cannot be read: its format is not recognized by the GD extension.'
        );
    }

    private function createMainImage(string $image, Configuration $configuration): GdImage
    {
        $mainImage = $this->read($image);
        imagealphablending($mainImage, true);
        imagesavealpha($mainImage, true);

        if ($configuration->imageScale !== null) {
            $mainImage = $this->applyScale($mainImage, $configuration->imageScale);
        }

        $srcWidth = imagesx($mainImage);
        $srcHeight = imagesy($mainImage);
        // The whole source has to fit inside the target, centred, exactly like Imagick's best fit. Matching one side
        // only used to let the other overflow the canvas, cropping every source that was less square than its target.
        $ratio = min($configuration->width / $srcWidth, $configuration->height / $srcHeight);
        $newWidth = max(1, (int) round($srcWidth * $ratio));
        $newHeight = max(1, (int) round($srcHeight * $ratio));

        $newImage = $this->transparentCanvas($configuration->width, $configuration->height);
        imagecopyresampled(
            $newImage,
            $mainImage,
            (int) (($configuration->width - $newWidth) / 2),
            (int) (($configuration->height - $newHeight) / 2),
            0,
            0,
            $newWidth,
            $newHeight,
            $srcWidth,
            $srcHeight,
        );

        if ($configuration->monochrome) {
            $this->desaturate($newImage);
        }

        return $newImage;
    }

    /**
     * Shrinks the image inside its own bounds, leaving the freed band transparent.
     */
    private function applyScale(GdImage $image, int $imageScale): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $newWidth = max(1, (int) ($width * $imageScale / 100));
        $newHeight = max(1, (int) ($height * $imageScale / 100));

        $canvas = $this->transparentCanvas($width, $height);
        imagecopyresampled(
            $canvas,
            $image,
            (int) (($width - $newWidth) / 2),
            (int) (($height - $newHeight) / 2),
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height,
        );

        return $canvas;
    }

    /**
     * Turns the image to greyscale, preserving its alpha channel.
     *
     * IMG_FILTER_GRAYSCALE would be one native call, but it weighs the channels the Rec. 601 way while ImageMagick
     * weighs them the Rec. 709 way, which puts the two processors up to 22 levels apart on a saturated colour.
     */
    private function desaturate(GdImage $image): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                $luminance = (int) round(
                    0.2126 * (($color >> 16) & 0xFF)
                    + 0.7152 * (($color >> 8) & 0xFF)
                    + 0.0722 * ($color & 0xFF)
                );
                imagesetpixel(
                    $image,
                    $x,
                    $y,
                    ($color & 0x7F000000) | ($luminance << 16) | ($luminance << 8) | $luminance
                );
            }
        }

        imagealphablending($image, true);
    }

    private function createBackground(Configuration $configuration): GdImage
    {
        if ($configuration->backgroundColor === null) {
            $background = $this->transparentCanvas($configuration->width, $configuration->height);
            imagealphablending($background, true);

            return $background;
        }

        $backgroundColor = Color::fromString($configuration->backgroundColor);
        $background = $this->canvas($configuration->width, $configuration->height);
        $color = imagecolorallocatealpha(
            $background,
            $backgroundColor->red,
            $backgroundColor->green,
            $backgroundColor->blue,
            $backgroundColor->gdAlpha()
        );
        assert($color !== false);
        imagesavealpha($background, true);
        imagefill($background, 0, 0, $color);

        if ($configuration->borderRadius !== null) {
            $this->roundCorners($background, $configuration->borderRadius, $backgroundColor);
        }

        return $background;
    }

    /**
     * Cuts the four corners out of the flat background, carrying the coverage of the pixels the ellipse only crosses
     * in their alpha channel. Tracing an arc then flooding up to it left hard, aliased corners, where ImagickDraw has
     * been antialiasing its rounded rectangle all along.
     */
    private function roundCorners(GdImage $background, int $borderRadius, Color $color): void
    {
        $width = imagesx($background);
        $height = imagesy($background);
        $radiusX = max(1, (int) round($borderRadius * $width / 100));
        $radiusY = max(1, (int) round($borderRadius * $height / 100));
        // Only the pixels within roughly one and a half pixel of the ellipse are partly covered; the rest is decided
        // by the distance alone, which keeps the cost on the perimeter rather than on the whole corner.
        $band = 1.5 / min($radiusX, $radiusY);
        $opaqueAlpha = $color->gdAlpha();

        imagealphablending($background, false);
        imagesavealpha($background, true);

        $corners = [
            [$radiusX, $radiusY, 0, 0],
            [$width - $radiusX, $radiusY, $width - $radiusX, 0],
            [$radiusX, $height - $radiusY, 0, $height - $radiusY],
            [$width - $radiusX, $height - $radiusY, $width - $radiusX, $height - $radiusY],
        ];
        foreach ($corners as [$centerX, $centerY, $originX, $originY]) {
            for ($x = $originX; $x < $originX + $radiusX; $x++) {
                for ($y = $originY; $y < $originY + $radiusY; $y++) {
                    $distanceX = ($x + 0.5 - $centerX) / $radiusX;
                    $distanceY = ($y + 0.5 - $centerY) / $radiusY;
                    $distance = sqrt($distanceX ** 2 + $distanceY ** 2);
                    if ($distance <= 1 - $band) {
                        continue;
                    }
                    $coverage = $distance >= 1 + $band
                        ? 0.0
                        : $this->coverage($x, $y, $centerX, $centerY, $radiusX, $radiusY);
                    $alpha = (int) round(127 - $coverage * (127 - $opaqueAlpha));
                    imagesetpixel(
                        $background,
                        $x,
                        $y,
                        ($alpha << 24) | ($color->red << 16) | ($color->green << 8) | $color->blue
                    );
                }
            }
        }

        imagealphablending($background, true);
    }

    /**
     * The share of the pixel the ellipse covers, sampled on a four by four grid.
     */
    private function coverage(int $x, int $y, int $centerX, int $centerY, int $radiusX, int $radiusY): float
    {
        $samples = 4;
        $inside = 0;
        for ($column = 0; $column < $samples; $column++) {
            for ($row = 0; $row < $samples; $row++) {
                $distanceX = ($x + ($column + 0.5) / $samples - $centerX) / $radiusX;
                $distanceY = ($y + ($row + 0.5) / $samples - $centerY) / $radiusY;
                if ($distanceX ** 2 + $distanceY ** 2 <= 1.0) {
                    $inside++;
                }
            }
        }

        return $inside / $samples ** 2;
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private function transparentCanvas(int $width, int $height): GdImage
    {
        $canvas = $this->canvas($width, $height);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        assert($transparent !== false);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, $transparent);

        return $canvas;
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private function canvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            throw new RuntimeException(sprintf('Unable to allocate a %dx%d image.', $width, $height));
        }

        return $canvas;
    }
}
