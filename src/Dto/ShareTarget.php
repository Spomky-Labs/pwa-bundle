<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

final class ShareTarget
{
    public Url $action;

    public null|string $method = null;

    public null|string $enctype = null;

    public null|ShareTargetParameters $params = null;
}
