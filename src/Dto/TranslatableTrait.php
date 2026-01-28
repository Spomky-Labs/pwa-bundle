<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

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
        if (! interface_exists(TranslatableInterface::class) || $data === null) {
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
