<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class FileHandler
{
    public string $action;

    /**
     * @var array<string, mixed>
     */
    #[SerializedName('action_params')]
    public array $actionParameters = [];

    /**
     * @var array<string, string[]>
     */
    public array $accept;
}
