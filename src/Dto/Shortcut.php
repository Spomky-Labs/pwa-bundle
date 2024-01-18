<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Contracts\Translation\TranslatableInterface;

final class Shortcut
{
    use TranslatableTrait;

    public string $name;

    #[SerializedName('short_name')]
    public null|string $shortName = null;

    public null|string $description = null;

    public Url $url;

    /**
     * @var array<Icon>
     */
    public array $icons = [];

    public function getName(): string|TranslatableInterface
    {
        return $this->provideTranslation($this->name);
    }

    #[SerializedName('short_name')]
    public function getShortName(): null|string|TranslatableInterface
    {
        return $this->provideTranslation($this->shortName);
    }

    public function getDescription(): null|string|TranslatableInterface
    {
        return $this->provideTranslation($this->description);
    }
}
