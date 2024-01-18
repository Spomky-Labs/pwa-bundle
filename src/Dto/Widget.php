<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Contracts\Translation\TranslatableInterface;

final class Widget
{
    use TranslatableTrait;

    public string $name;

    #[SerializedName('short_name')]
    public null|string $shortName = null;

    public null|string $description = null;

    /**
     * @var array<Icon>
     */
    public array $icons = [];

    /**
     * @var array<Screenshot>
     */
    public array $screenshots = [];

    public null|string $tag = null;

    public null|string $template = null;

    #[SerializedName('ms_ac_template')]
    public Url $adaptativeCardTemplate;

    public null|Url $data = null;

    public null|string $type = null;

    public null|bool $auth = null;

    public null|int $update = null;

    public bool $multiple = true;

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
