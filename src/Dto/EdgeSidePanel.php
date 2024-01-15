<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class EdgeSidePanel
{
    #[SerializedName('preferred_width')]
    public null|int $preferredWidth = null;
}
