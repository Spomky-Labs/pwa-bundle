<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\SerializedName;
use function is_string;

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
            $future = $now->add(DateInterval::createFromDateString($this->maxAge));
            return abs($future->getTimestamp() - $now->getTimestamp());
        }
        return $this->maxAge;
    }
}
