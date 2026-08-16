<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use function count;
use InvalidArgumentException;
use function mb_str_split;
use function mb_strlen;
use function mb_strtolower;
use const PREG_SPLIT_NO_EMPTY;
use function sprintf;

/**
 * A colour taken from the bundle configuration, resolved to the channels the GD extension expects.
 *
 * GD only knows about integer channels, so every notation the configuration documents — a CSS colour name or an
 * hexadecimal triplet — has to be resolved here first. Feeding hexdec() with whatever the configuration holds is not an
 * option: it emits a deprecation for every character that is not hexadecimal, so "red" both warns and resolves to a
 * meaningless dark colour.
 *
 * @internal
 */
final readonly class Color
{
    /**
     * The CSS Color Module Level 4 named colours.
     *
     * ImageMagick resolves nearly the same table, with three exceptions kept on the CSS side here: it reads "gray" as
     * #7E7E7E and "grey" as the X11 #BEBEBE instead of #808080, and it does not know "rebeccapurple" at all.
     *
     * @var array<string, string>
     */
    private const NAMED_COLORS = [
        'aliceblue' => 'f0f8ff',
        'antiquewhite' => 'faebd7',
        'aqua' => '00ffff',
        'aquamarine' => '7fffd4',
        'azure' => 'f0ffff',
        'beige' => 'f5f5dc',
        'bisque' => 'ffe4c4',
        'black' => '000000',
        'blanchedalmond' => 'ffebcd',
        'blue' => '0000ff',
        'blueviolet' => '8a2be2',
        'brown' => 'a52a2a',
        'burlywood' => 'deb887',
        'cadetblue' => '5f9ea0',
        'chartreuse' => '7fff00',
        'chocolate' => 'd2691e',
        'coral' => 'ff7f50',
        'cornflowerblue' => '6495ed',
        'cornsilk' => 'fff8dc',
        'crimson' => 'dc143c',
        'cyan' => '00ffff',
        'darkblue' => '00008b',
        'darkcyan' => '008b8b',
        'darkgoldenrod' => 'b8860b',
        'darkgray' => 'a9a9a9',
        'darkgreen' => '006400',
        'darkgrey' => 'a9a9a9',
        'darkkhaki' => 'bdb76b',
        'darkmagenta' => '8b008b',
        'darkolivegreen' => '556b2f',
        'darkorange' => 'ff8c00',
        'darkorchid' => '9932cc',
        'darkred' => '8b0000',
        'darksalmon' => 'e9967a',
        'darkseagreen' => '8fbc8f',
        'darkslateblue' => '483d8b',
        'darkslategray' => '2f4f4f',
        'darkslategrey' => '2f4f4f',
        'darkturquoise' => '00ced1',
        'darkviolet' => '9400d3',
        'deeppink' => 'ff1493',
        'deepskyblue' => '00bfff',
        'dimgray' => '696969',
        'dimgrey' => '696969',
        'dodgerblue' => '1e90ff',
        'firebrick' => 'b22222',
        'floralwhite' => 'fffaf0',
        'forestgreen' => '228b22',
        'fuchsia' => 'ff00ff',
        'gainsboro' => 'dcdcdc',
        'ghostwhite' => 'f8f8ff',
        'gold' => 'ffd700',
        'goldenrod' => 'daa520',
        'gray' => '808080',
        'green' => '008000',
        'greenyellow' => 'adff2f',
        'grey' => '808080',
        'honeydew' => 'f0fff0',
        'hotpink' => 'ff69b4',
        'indianred' => 'cd5c5c',
        'indigo' => '4b0082',
        'ivory' => 'fffff0',
        'khaki' => 'f0e68c',
        'lavender' => 'e6e6fa',
        'lavenderblush' => 'fff0f5',
        'lawngreen' => '7cfc00',
        'lemonchiffon' => 'fffacd',
        'lightblue' => 'add8e6',
        'lightcoral' => 'f08080',
        'lightcyan' => 'e0ffff',
        'lightgoldenrodyellow' => 'fafad2',
        'lightgray' => 'd3d3d3',
        'lightgreen' => '90ee90',
        'lightgrey' => 'd3d3d3',
        'lightpink' => 'ffb6c1',
        'lightsalmon' => 'ffa07a',
        'lightseagreen' => '20b2aa',
        'lightskyblue' => '87cefa',
        'lightslategray' => '778899',
        'lightslategrey' => '778899',
        'lightsteelblue' => 'b0c4de',
        'lightyellow' => 'ffffe0',
        'lime' => '00ff00',
        'limegreen' => '32cd32',
        'linen' => 'faf0e6',
        'magenta' => 'ff00ff',
        'maroon' => '800000',
        'mediumaquamarine' => '66cdaa',
        'mediumblue' => '0000cd',
        'mediumorchid' => 'ba55d3',
        'mediumpurple' => '9370db',
        'mediumseagreen' => '3cb371',
        'mediumslateblue' => '7b68ee',
        'mediumspringgreen' => '00fa9a',
        'mediumturquoise' => '48d1cc',
        'mediumvioletred' => 'c71585',
        'midnightblue' => '191970',
        'mintcream' => 'f5fffa',
        'mistyrose' => 'ffe4e1',
        'moccasin' => 'ffe4b5',
        'navajowhite' => 'ffdead',
        'navy' => '000080',
        'oldlace' => 'fdf5e6',
        'olive' => '808000',
        'olivedrab' => '6b8e23',
        'orange' => 'ffa500',
        'orangered' => 'ff4500',
        'orchid' => 'da70d6',
        'palegoldenrod' => 'eee8aa',
        'palegreen' => '98fb98',
        'paleturquoise' => 'afeeee',
        'palevioletred' => 'db7093',
        'papayawhip' => 'ffefd5',
        'peachpuff' => 'ffdab9',
        'peru' => 'cd853f',
        'pink' => 'ffc0cb',
        'plum' => 'dda0dd',
        'powderblue' => 'b0e0e6',
        'purple' => '800080',
        'rebeccapurple' => '663399',
        'red' => 'ff0000',
        'rosybrown' => 'bc8f8f',
        'royalblue' => '4169e1',
        'saddlebrown' => '8b4513',
        'salmon' => 'fa8072',
        'sandybrown' => 'f4a460',
        'seagreen' => '2e8b57',
        'seashell' => 'fff5ee',
        'sienna' => 'a0522d',
        'silver' => 'c0c0c0',
        'skyblue' => '87ceeb',
        'slateblue' => '6a5acd',
        'slategray' => '708090',
        'slategrey' => '708090',
        'snow' => 'fffafa',
        'springgreen' => '00ff7f',
        'steelblue' => '4682b4',
        'tan' => 'd2b48c',
        'teal' => '008080',
        'thistle' => 'd8bfd8',
        'tomato' => 'ff6347',
        'turquoise' => '40e0d0',
        'violet' => 'ee82ee',
        'wheat' => 'f5deb3',
        'white' => 'ffffff',
        'whitesmoke' => 'f5f5f5',
        'yellow' => 'ffff00',
        'yellowgreen' => '9acd32',
    ];

    /**
     * @param int<0, 255> $red
     * @param int<0, 255> $green
     * @param int<0, 255> $blue
     * @param int<0, 255> $opacity 0 is fully transparent, 255 fully opaque, as CSS states it.
     */
    private function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public int $opacity,
    ) {
    }

    /**
     * Accepts a CSS colour name, the "transparent" keyword, an hexadecimal notation with 3, 4, 6 or 8 digits and an
     * optional leading "#", or one of the rgb(), rgba(), hsl() and hsla() functional notations.
     */
    public static function fromString(string $color): self
    {
        $normalized = mb_strtolower(trim($color));
        if ($normalized === 'transparent') {
            return new self(0, 0, 0, 0);
        }
        if (str_contains($normalized, '(')) {
            return self::fromFunctionalNotation($color, $normalized);
        }
        $normalized = self::NAMED_COLORS[$normalized] ?? $normalized;

        if (preg_match('/^#?([0-9a-f]{3,8})$/', $normalized, $matches) !== 1) {
            throw self::unsupported($color);
        }
        $hex = $matches[1];
        $channels = match (mb_strlen($hex)) {
            // The short notations repeat every digit: "#1a2" is "#11aa22".
            3, 4 => array_map(static fn (string $digit): string => $digit . $digit, mb_str_split($hex)),
            6, 8 => mb_str_split($hex, 2),
            default => throw self::unsupported($color),
        };

        return new self(
            self::clamp((int) hexdec($channels[0])),
            self::clamp((int) hexdec($channels[1])),
            self::clamp((int) hexdec($channels[2])),
            isset($channels[3]) ? self::clamp((int) hexdec($channels[3])) : 255,
        );
    }

    /**
     * The transparency of the colour on the scale the GD extension uses: 0 is fully opaque, 127 fully transparent.
     *
     * @return int<0, 127>
     */
    public function gdAlpha(): int
    {
        return max(0, min(127, (int) round((255 - $this->opacity) * 127 / 255)));
    }

    public function isTransparent(): bool
    {
        return $this->opacity === 0;
    }

    /**
     * The "#RRGGBBAA" notation, which every ImagickPixel understands.
     */
    public function toHex(): string
    {
        return sprintf('#%02X%02X%02X%02X', $this->red, $this->green, $this->blue, $this->opacity);
    }

    private static function fromFunctionalNotation(string $color, string $normalized): self
    {
        if (preg_match('/^(rgba?|hsla?)\(\s*([^()]*?)\s*\)$/', $normalized, $matches) !== 1) {
            throw self::unsupported($color);
        }
        // Both the legacy "rgb(255, 0, 0)" and the modern "rgb(255 0 0 / 50%)" separators are accepted.
        $arguments = preg_split('#[\s,/]+#', $matches[2], -1, PREG_SPLIT_NO_EMPTY);
        if ($arguments === false || count($arguments) < 3 || count($arguments) > 4) {
            throw self::unsupported($color);
        }
        $opacity = isset($arguments[3]) ? self::opacity($color, $arguments[3]) : 255;

        if (str_starts_with($matches[1], 'rgb')) {
            return new self(
                self::rgbChannel($color, $arguments[0]),
                self::rgbChannel($color, $arguments[1]),
                self::rgbChannel($color, $arguments[2]),
                $opacity,
            );
        }

        return self::fromHsl(
            self::number($color, $arguments[0], 'deg'),
            self::number($color, $arguments[1], '%') / 100,
            self::number($color, $arguments[2], '%') / 100,
            $opacity,
        );
    }

    /**
     * @param int<0, 255> $opacity
     */
    private static function fromHsl(float $hue, float $saturation, float $lightness, int $opacity): self
    {
        $hue = fmod(fmod($hue, 360) + 360, 360) / 60;
        $saturation = max(0.0, min(1.0, $saturation));
        $lightness = max(0.0, min(1.0, $lightness));

        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $second = $chroma * (1 - abs(fmod($hue, 2.0) - 1));
        $offset = $lightness - $chroma / 2;

        [$red, $green, $blue] = match ((int) $hue) {
            0 => [$chroma, $second, 0.0],
            1 => [$second, $chroma, 0.0],
            2 => [0.0, $chroma, $second],
            3 => [0.0, $second, $chroma],
            4 => [$second, 0.0, $chroma],
            default => [$chroma, 0.0, $second],
        };

        return new self(
            self::clamp((int) round(($red + $offset) * 255)),
            self::clamp((int) round(($green + $offset) * 255)),
            self::clamp((int) round(($blue + $offset) * 255)),
            $opacity,
        );
    }

    /**
     * @return int<0, 255>
     */
    private static function rgbChannel(string $color, string $argument): int
    {
        return self::clamp((int) round(
            str_ends_with($argument, '%')
                ? self::number($color, $argument, '%') * 255 / 100
                : self::number($color, $argument)
        ));
    }

    /**
     * @return int<0, 255>
     */
    private static function opacity(string $color, string $argument): int
    {
        // The alpha channel is a fraction of one, unless it carries a percent sign.
        return self::clamp((int) round(
            str_ends_with($argument, '%')
                ? self::number($color, $argument, '%') * 255 / 100
                : self::number($color, $argument) * 255
        ));
    }

    private static function number(string $color, string $argument, null|string $unit = null): float
    {
        $value = $unit === null ? $argument : rtrim($argument, $unit);
        if (preg_match('/^[+-]?(?:\d+\.?\d*|\.\d+)$/', $value) !== 1) {
            throw self::unsupported($color);
        }

        return (float) $value;
    }

    /**
     * @return int<0, 255>
     */
    private static function clamp(int $value): int
    {
        return max(0, min(255, $value));
    }

    private static function unsupported(string $color): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            'The color "%s" is not supported. Use a CSS color name, "transparent", an hexadecimal notation such as "#f00", "#f00c", "#ff0000" or "#ff0000cc", or one of the rgb(), rgba(), hsl() and hsla() notations.',
            $color
        ));
    }
}
