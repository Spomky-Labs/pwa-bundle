<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use function class_exists;
use function is_array;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

trait TranslatableTrait
{
    /**
     * @template T of null|string|array<string>
     *
     * @param T $data
     *
     * @phpstan-return (T is null ? null : (T is string ? string|TranslatableInterface : array<string|TranslatableInterface>))
     */
    public function provideTranslation(null|string|array $data): null|string|TranslatableInterface|array
    {
        // symfony/translation-contracts often comes along on its own (twig-bridge, security-core, …), so the
        // interface being there proves nothing: TranslatableMessage lives in symfony/translation. Without that
        // component there is nothing to translate with, and the configured text is the final text.
        if ($data === null || ! class_exists(TranslatableMessage::class)) {
            return $data;
        }
        if (is_array($data)) {
            return array_map(
                static fn (string $value): TranslatableInterface => new TranslatableMessage($value, [], 'pwa'),
                $data
            );
        }

        return new TranslatableMessage($data, [], 'pwa');
    }
}
