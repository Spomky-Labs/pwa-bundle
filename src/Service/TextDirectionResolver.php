<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function array_slice;
use function count;
use function in_array;

/**
 * Resolves the writing direction of a locale, so that the localized manifest members can declare a `dir` when it
 * differs from the one of the manifest itself.
 */
final readonly class TextDirectionResolver
{
    /**
     * Primary language subtags written from right to left.
     *
     * @var array<string>
     */
    private const RTL_LANGUAGES = [
        'ar',
        'arc',
        'ckb',
        'dv',
        'fa',
        'he',
        'iw',
        'khw',
        'ks',
        'nqo',
        'pnb',
        'prs',
        'ps',
        'sd',
        'syr',
        'ug',
        'ur',
        'yi',
    ];

    /**
     * Script subtags written from right to left. They take precedence over the language, as `uz-Arab` is RTL while
     * `uz-Latn` is not.
     *
     * @var array<string>
     */
    private const RTL_SCRIPTS = ['adlm', 'arab', 'aran', 'hebr', 'nkoo', 'rohg', 'syrc', 'thaa', 'yezi'];

    /**
     * @param array<string, string> $directions Locale to direction map, overriding the detection
     */
    public function __construct(
        private array $directions = [],
    ) {
    }

    public function resolve(string $locale): string
    {
        $subtags = explode('-', strtolower(str_replace('_', '-', $locale)));
        $overrides = array_change_key_case($this->directions);

        for ($length = count($subtags); $length > 0; --$length) {
            $candidate = implode('-', array_slice($subtags, 0, $length));
            if (isset($overrides[$candidate])) {
                return $overrides[$candidate];
            }
        }

        foreach ($subtags as $subtag) {
            if (in_array($subtag, self::RTL_SCRIPTS, true)) {
                return 'rtl';
            }
        }

        return in_array($subtags[0], self::RTL_LANGUAGES, true) ? 'rtl' : 'ltr';
    }
}
