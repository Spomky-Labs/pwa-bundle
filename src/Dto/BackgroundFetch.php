<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @deprecated since 1.6.0, will be removed in 2.0.0. It imposes an IndexedDB schema and a
 * client/service worker protocol that no application reuses as-is. Handle the
 * "backgroundfetchsuccess" event in your own service worker source instead.
 */
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
