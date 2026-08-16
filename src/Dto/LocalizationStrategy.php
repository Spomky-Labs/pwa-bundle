<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

/**
 * How the translations of the manifest members are delivered to the browsers.
 */
enum LocalizationStrategy: string
{
    /**
     * One manifest file per enabled locale. The `{locale}` placeholder of the public URL is replaced by the
     * compiled locale and the template has to render the URL matching the current request.
     */
    case FILES = 'files';

    /**
     * A single, locale agnostic manifest file carrying every translation through the `*_localized` members.
     */
    case INLINE = 'inline';

    /**
     * One manifest file per enabled locale, each of them also carrying the `*_localized` members. Browsers that
     * do not support the localized members simply ignore them.
     */
    case BOTH = 'both';

    public function compilesOneFilePerLocale(): bool
    {
        return $this !== self::INLINE;
    }

    public function embedsLocalizedMembers(): bool
    {
        return $this !== self::FILES;
    }
}
