<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use InvalidArgumentException;
use function mb_str_split;
use function mb_strlen;
use function mb_strtolower;
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
     * @param int<0, 127> $alpha On the GD scale: 0 is fully opaque, 127 fully transparent.
     */
    private function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public int $alpha,
    ) {
    }

    /**
     * Accepts a CSS colour name, the "transparent" keyword, or an hexadecimal notation with 3, 4, 6 or 8 digits and an
     * optional leading "#".
     */
    public static function fromString(string $color): self
    {
        $normalized = mb_strtolower(trim($color));
        if ($normalized === 'transparent') {
            return new self(0, 0, 0, 127);
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
            self::channel($channels[0]),
            self::channel($channels[1]),
            self::channel($channels[2]),
            // CSS states the opacity, GD the transparency, on a 128 step scale.
            isset($channels[3]) ? self::alpha(self::channel($channels[3])) : 0,
        );
    }

    public function isTransparent(): bool
    {
        return $this->alpha === 127;
    }

    /**
     * @return int<0, 255>
     */
    private static function channel(string $hex): int
    {
        return max(0, min(255, (int) hexdec($hex)));
    }

    /**
     * @param int<0, 255> $opacity
     *
     * @return int<0, 127>
     */
    private static function alpha(int $opacity): int
    {
        return max(0, min(127, (int) round((255 - $opacity) * 127 / 255)));
    }

    private static function unsupported(string $color): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            'The color "%s" is not supported. Use a CSS color name, "transparent", or an hexadecimal notation such as "#f00", "#f00c", "#ff0000" or "#ff0000cc".',
            $color
        ));
    }
}
