<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function count;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use SpomkyLabs\PwaBundle\Dto\SpeculationRule;
use SpomkyLabs\PwaBundle\Dto\SpeculationRules;
use SpomkyLabs\PwaBundle\Dto\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SpeculationRulesBuilder
{
    private ?SpeculationRules $speculationRules = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        #[Autowire(param: 'spomky_labs_pwa.speculation_rules.config')]
        private readonly array $config,
    ) {
    }

    public function create(): SpeculationRules
    {
        if ($this->speculationRules === null) {
            $this->speculationRules = $this->denormalizer->denormalize(
                $this->config,
                SpeculationRules::class,
                'json'
            );
        }

        return $this->speculationRules;
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * Generate the JSON for the speculation rules script.
     *
     * @param array<string> $resolvedPrefetchUrls Pre-resolved URLs to add to prefetch list
     * @param array<string> $resolvedPrerenderUrls Pre-resolved URLs to add to prerender list
     */
    public function generateJson(array $resolvedPrefetchUrls = [], array $resolvedPrerenderUrls = []): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $rules = $this->create();
        $output = [];

        $prefetchRules = $this->buildRules($rules->prefetch, $resolvedPrefetchUrls);
        if ($prefetchRules !== []) {
            $output['prefetch'] = $prefetchRules;
        }

        $prerenderRules = $this->buildRules($rules->prerender, $resolvedPrerenderUrls);
        if ($prerenderRules !== []) {
            $output['prerender'] = $prerenderRules;
        }

        if ($output === []) {
            return null;
        }

        return json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<SpeculationRule> $rules
     * @param array<string> $additionalUrls
     * @return array<array<string, mixed>>
     */
    private function buildRules(array $rules, array $additionalUrls = []): array
    {
        $output = [];

        foreach ($rules as $rule) {
            $ruleOutput = $this->buildSingleRule($rule);
            if ($ruleOutput !== null) {
                $output[] = $ruleOutput;
            }
        }

        // Add additional URLs as a list rule if provided
        if ($additionalUrls !== []) {
            $output[] = [
                'source' => 'list',
                'urls' => $additionalUrls,
            ];
        }

        return $output;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSingleRule(SpeculationRule $rule): ?array
    {
        $output = [
            'source' => $rule->source,
        ];

        if ($rule->source === SpeculationRule::SOURCE_LIST) {
            $urls = array_map(static fn (Url $url): string => $url->path, $rule->urls);
            if ($urls === []) {
                return null;
            }
            $output['urls'] = $urls;
        } else {
            // Document source - needs where clause
            $where = $this->buildWhereClause($rule);
            if ($where === null) {
                return null;
            }
            $output['where'] = $where;
        }

        if ($rule->eagerness !== SpeculationRule::EAGERNESS_MODERATE) {
            $output['eagerness'] = $rule->eagerness;
        }

        if ($rule->referrerPolicy !== null) {
            $output['referrer_policy'] = $rule->referrerPolicy;
        }

        return $output;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildWhereClause(SpeculationRule $rule): ?array
    {
        $conditions = [];

        if ($rule->selectorMatches !== null) {
            $conditions[] = [
                'selector_matches' => $rule->selectorMatches,
            ];
        }

        if ($rule->hrefMatches !== null) {
            $conditions[] = [
                'href_matches' => $rule->hrefMatches,
            ];
        }

        if ($conditions === []) {
            return null;
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        return [
            'and' => $conditions,
        ];
    }
}
