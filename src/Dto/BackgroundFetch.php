<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BackgroundFetch
{
    public bool $enabled = false;

    #[SerializedName('db_name')]
    public null|string $dbName = 'bgfetch-completed';

    #[SerializedName('progress_url')]
    public null|Url $progressUrl = null;

    #[SerializedName('success_url')]
    public null|Url $successUrl = null;

    #[SerializedName('success_message')]
    public null|string $successMessage = null;

    #[SerializedName('failure_message')]
    public null|string $failureMessage = null;
}
