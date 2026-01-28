<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class SpeculationRule
{
    public const SOURCE_LIST = 'list';

    public const SOURCE_DOCUMENT = 'document';

    public const EAGERNESS_IMMEDIATE = 'immediate';

    public const EAGERNESS_EAGER = 'eager';

    public const EAGERNESS_MODERATE = 'moderate';

    public const EAGERNESS_CONSERVATIVE = 'conservative';

    /**
     * The source type: 'list' for explicit URLs, 'document' for link matching rules.
     */
    public string $source = self::SOURCE_DOCUMENT;

    /**
     * For 'list' source: explicit URLs to prefetch/prerender.
     * @var array<Url>
     */
    public array $urls = [];

    /**
     * For 'document' source: CSS selector to match links.
     */
    #[SerializedName('selector_matches')]
    public ?string $selectorMatches = null;

    /**
     * For 'document' source: URL pattern to match href attributes.
     */
    #[SerializedName('href_matches')]
    public ?string $hrefMatches = null;

    /**
     * Eagerness level: 'immediate', 'eager', 'moderate', or 'conservative'.
     */
    public string $eagerness = self::EAGERNESS_MODERATE;

    /**
     * Whether to require no-vary-search header matching.
     */
    #[SerializedName('expects_no_vary_search')]
    public ?string $expectsNoVarySearch = null;

    /**
     * Referrer policy for the speculative request.
     */
    #[SerializedName('referrer_policy')]
    public ?string $referrerPolicy = null;

    /**
     * Whether this rule should only apply when requirements are met.
     *
     * @var array<string>|null
     */
    #[SerializedName('requires')]
    public ?array $requires = null;
}
