<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ShareTarget
{
    public string $action;

    /**
     * @var array<string, mixed>
     */
    #[SerializedName('action_params')]
    public array $actionParameters = [];

    public null|string $method = null;

    public null|string $enctype = null;

    public null|ShareTargetParameters $params = null;
}
