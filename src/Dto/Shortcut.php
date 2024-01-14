<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Shortcut
{
    public string $name;

    #[SerializedName('short_name')]
    public null|string $shortName = null;

    public null|string $description = null;

    public null|string $url = null;

    public null|string $path = null;

    /**
     * @var array<Icon>
     */
    public array $icons = [];
}
