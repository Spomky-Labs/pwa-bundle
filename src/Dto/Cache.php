<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use function date_interval_create_from_date_string;
use DateTimeImmutable;
use InvalidArgumentException;
use function is_string;
use function sprintf;
use Symfony\Component\Serializer\Attribute\SerializedName;

abstract class Cache
{
    #[SerializedName('cache_name')]
    public null|string $cacheName = null;

    #[SerializedName('max_age')]
    public null|string|int $maxAge = null;

    #[SerializedName('max_entries')]
    public null|int $maxEntries = 60;

    public function maxAgeInSeconds(): null|int
    {
        if ($this->maxAge === null) {
            return null;
        }
        if (is_string($this->maxAge)) {
            $now = new DateTimeImmutable();
            // The function returns false on every supported PHP version; the static method throws from PHP 8.3 on.
            $interval = @date_interval_create_from_date_string($this->maxAge);
            if ($interval === false) {
                throw new InvalidArgumentException(sprintf('Invalid max age "%s".', $this->maxAge));
            }
            $future = $now->add($interval);
            return abs($future->getTimestamp() - $now->getTimestamp());
        }
        return $this->maxAge;
    }
}
