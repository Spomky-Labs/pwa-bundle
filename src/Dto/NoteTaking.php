<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class NoteTaking
{
    #[SerializedName('note_taking_url')]
    public null|Url $url = null;
}
