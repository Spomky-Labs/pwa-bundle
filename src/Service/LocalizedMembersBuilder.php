<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function is_string;
use SpomkyLabs\PwaBundle\Dto\LocalizationStrategy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds the `*_localized` manifest members out of the enabled locales and the `pwa` translation domain.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Manifest/Reference/*_localized
 */
final readonly class LocalizedMembersBuilder
{
    /**
     * Serialization context key carrying the `dir` of the manifest down to the nested objects.
     */
    public const MANIFEST_DIR_KEY = 'pwa_manifest_dir';

    /**
     * A manifest without an explicit `dir` is served left to right by every browser we target, so this is the
     * direction the localized members are compared against.
     */
    private const DEFAULT_DIRECTION = 'ltr';

    private LocalizationStrategy $strategy;

    /**
     * @param array<string> $locales
     */
    public function __construct(
        #[Autowire(param: 'spomky_labs_pwa.manifest.localization_strategy')]
        string $strategy,
        #[Autowire(param: 'kernel.enabled_locales')]
        private array $locales,
        private TextDirectionResolver $directionResolver,
        private null|TranslatorInterface $translator = null,
    ) {
        $this->strategy = LocalizationStrategy::from($strategy);
    }

    /**
     * Appends the `*_localized` members to an already normalized manifest or shortcut.
     *
     * @param array<string, mixed>       $normalized
     * @param array<string, null|string> $members    Member name (as serialized) to translation key
     *
     * @return array<string, mixed>
     */
    public function decorate(array $normalized, array $members, null|string $manifestDir): array
    {
        if (! $this->strategy->embedsLocalizedMembers()) {
            unset($normalized['icons_localized']);

            return $normalized;
        }

        foreach ($members as $member => $translationKey) {
            $localized = $this->buildTextMember($translationKey, $normalized[$member] ?? null, $manifestDir);
            if ($localized !== []) {
                $normalized[$member . '_localized'] = $localized;
            }
        }

        if (($normalized['icons_localized'] ?? []) === []) {
            unset($normalized['icons_localized']);
        }

        return $normalized;
    }

    /**
     * @return array<string, string|array{value: string, dir: string}>
     */
    private function buildTextMember(
        null|string $translationKey,
        mixed $defaultValue,
        null|string $manifestDir
    ): array {
        if ($this->translator === null || $translationKey === null || $translationKey === '') {
            return [];
        }
        if (! is_string($defaultValue)) {
            return [];
        }

        $baseDirection = $manifestDir ?? self::DEFAULT_DIRECTION;
        $localized = [];
        foreach ($this->locales as $locale) {
            $translation = $this->translator->trans($translationKey, [], 'pwa', $locale);
            // Either the locale resolves to the value already carried by the non-localized member, or it has no
            // translation at all and the translator handed the key back. Both are dead weight in the manifest.
            if ($translation === $defaultValue || $translation === $translationKey) {
                continue;
            }

            $direction = $this->directionResolver->resolve($locale);
            $localized[$locale] = $direction === $baseDirection ? $translation : [
                'value' => $translation,
                'dir' => $direction,
            ];
        }

        return $localized;
    }
}
