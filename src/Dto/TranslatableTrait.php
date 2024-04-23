<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use function is_array;

trait TranslatableTrait
{
    /**
     * @param null|string|array<string> $data
     *
     * @return null|string|TranslatableInterface|array<string|TranslatableInterface>
     */
    public function provideTranslation(null|string|array $data): null|string|TranslatableInterface|array
    {
        if (! interface_exists(TranslatableInterface::class) || $data === null) {
            return $data;
        }
        if (is_array($data)) {
            return array_map(
                fn (string $value): TranslatableInterface => new TranslatableMessage($value, [], 'pwa'),
                $data
            );
        }

        return new TranslatableMessage($data, [], 'pwa');
    }
}
