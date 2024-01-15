<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Widget
{
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
    public string $adaptativeCardTemplate;

    public null|string $data = null;

    public null|string $type = null;

    public null|bool $auth = null;

    public null|int $update = null;

    public bool $multiple = true;
}
