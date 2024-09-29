<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use GdImage;
use InvalidArgumentException;
use function assert;
use function is_string;
use function mb_strlen;

final readonly class GDImageProcessor implements ImageProcessorInterface
{
    use ConfigurationTrait;

    public function process(
        string $image,
        ?int $width,
        ?int $height,
        ?string $format,
        null|Configuration $configuration = null
    ): string {
        $configuration = $this->getConfiguration($image, $width, $height, $format, $configuration);
        $mainImage = $this->createMainImage($image, $configuration);
        $background = $this->createBackground($configuration);
        imagecopy($background, $mainImage, 0, 0, 0, 0, $configuration->width, $configuration->height);

        ob_start();
        switch ($configuration->format) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($background);
                break;
            case 'png':
                imagesavealpha($background, true);
                imagepng($background);
                break;
            case 'gif':
                imagegif($background);
                break;
            case 'ico':
                ob_start();
                imagesavealpha($background, true);
                imagepng($background);
                $pngData = ob_get_clean();
                assert(is_string($pngData));

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
                break;
            default:
                throw new InvalidArgumentException('Unsupported format');
        }
        return ob_get_clean();
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getSizes(string $image): array
    {
        $image = imagecreatefromstring($image);
        assert($image !== false);
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        return [
            'width' => $width,
            'height' => $height,
        ];
    }

    private function createMainImage(string $image, Configuration $configuration): GdImage
    {
        $mainImage = imagecreatefromstring($image);
        assert($mainImage !== false);
        $transparent = imagecolorallocatealpha($mainImage, 0, 0, 0, 127);
        assert($transparent !== false);
        imagealphablending($mainImage, true);
        imagesavealpha($mainImage, true);

        if ($configuration->imageScale !== null) {
            $width = imagesx($mainImage);
            $height = imagesy($mainImage);
            $newWidth = (int) ($width * $configuration->imageScale / 100);
            $newHeight = (int) ($height * $configuration->imageScale / 100);
            $dstWidth = (int) (($width - $newWidth) / 2);
            $dstHeight = (int) (($height - $newHeight) / 2);

            $newImage = imagecreatetruecolor($width, $height);
            assert($newImage !== false);
            imagefill($newImage, 0, 0, $transparent);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            imagecopyresampled(
                $newImage,
                $mainImage,
                $dstWidth,
                $dstHeight,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height,
            );
            $mainImage = $newImage;
        }

        /*if ($configuration->width === $configuration->height) {
         * $mainImage = imagescale($mainImage, $configuration->width, $configuration->height);
         * assert($mainImage !== false);
         * return $mainImage;
         * }*/

        $srcWidth = imagesx($mainImage);
        $srcHeight = imagesy($mainImage);
        if ($configuration->width >= $configuration->height) {
            $ratio = $srcHeight / $srcWidth;
            $newWidth = (int) ($configuration->height / $ratio);
            $newHeight = $configuration->height;
        } else {
            $ratio = $srcWidth / $srcHeight;
            $newWidth = $configuration->width;
            $newHeight = (int) ($configuration->width / $ratio);
        }

        $newImage = imagecreatetruecolor($configuration->width, $configuration->height);
        assert($newImage !== false);
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        imagefill($newImage, 0, 0, $transparent);

        $dstX = (int) (($configuration->width - $newWidth) / 2);
        $dstY = (int) (($configuration->height - $newHeight) / 2);
        imagecopyresampled(
            $newImage,
            $mainImage,
            $dstX,
            $dstY,
            0,
            0,
            $newWidth,
            $newHeight,
            $srcWidth,
            $srcHeight,
        );

        return $newImage;
    }

    private function createBackground(Configuration $configuration): GdImage
    {
        // Create a blank image
        $background = imagecreatetruecolor($configuration->width, $configuration->height);
        assert($background !== false);
        $transparent = imagecolorallocatealpha($background, 0, 0, 0, 127);
        assert($transparent !== false);

        // Fill the image with the transparent color
        if ($configuration->backgroundColor === null) {
            imagefill($background, 0, 0, $transparent);
            return $background;
        }

        $hex = ltrim($configuration->backgroundColor, '#');
        $r = (int) hexdec(mb_substr($hex, 0, 2));
        $g = (int) hexdec(mb_substr($hex, 2, 2));
        $b = (int) hexdec(mb_substr($hex, 4, 2));
        $color = imagecolorallocate($background, $r, $g, $b);
        assert($color !== false);
        imagefill($background, 0, 0, $color);

        if ($configuration->borderRadius === null) {
            return $background;
        }

        // Choose a ghost color (not used in the image)
        do {
            $r = random_int(0, 255);
            $g = random_int(0, 255);
            $b = random_int(0, 255);
        } while (imagecolorexact($background, $r, $g, $b) < 0);
        $ghostColor = imagecolorallocate($background, $r, $g, $b);
        assert($ghostColor !== false);

        // Draw the border radius
        $radiusX = (int) ($configuration->borderRadius * $configuration->width / 100);
        $radiusY = (int) ($configuration->borderRadius * $configuration->height / 100);

        imagearc($background, $radiusX - 1, $radiusY - 1, $radiusX * 2, $radiusY * 2, 180, 270, $ghostColor);
        imagefilltoborder($background, 0, 0, $ghostColor, $transparent);
        imagearc(
            $background,
            $configuration->width - $radiusX,
            $radiusY - 1,
            $radiusX * 2,
            $radiusY * 2,
            270,
            0,
            $ghostColor
        );
        imagefilltoborder($background, $configuration->width - 1, 0, $ghostColor, $transparent);
        imagearc(
            $background,
            $radiusX - 1,
            $configuration->height - $radiusY,
            $radiusX * 2,
            $radiusY * 2,
            90,
            180,
            $ghostColor
        );
        imagefilltoborder($background, 0, $configuration->height - 1, $ghostColor, $transparent);
        imagearc(
            $background,
            $configuration->width - $radiusX,
            $configuration->height - $radiusY,
            $radiusX * 2,
            $radiusY * 2,
            0,
            90,
            $ghostColor
        );
        imagefilltoborder(
            $background,
            $configuration->width - 1,
            $configuration->height - 1,
            $ghostColor,
            $transparent
        );

        imagesavealpha($background, true);
        for ($x = imagesx($background); $x--;) {
            for ($y = imagesy($background); $y--;) {
                $c = imagecolorat($background, $x, $y);
                if ($c === $ghostColor) {
                    imagesetpixel($background, $x, $y, $color);
                }
            }
        }

        return $background;
    }
}
