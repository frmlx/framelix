<?php

namespace Framelix\Framelix\Storable;

/**
 * SystemEventLog
 * @property int $category
 * @property string|null $message
 * @property mixed $params
 */
class SystemEventLog extends StorableExtended
{
    public const int CATEGORY_STORABLE_CREATED = 1;
    public const int CATEGORY_STORABLE_UPDATED = 2;
    public const int CATEGORY_STORABLE_DELETED = 3;
    public const int CATEGORY_LOGIN_FAILED = 4;
    public const int CATEGORY_LOGIN_SUCCESS = 5;

    /**
     * Create system event log
     * @param int $category
     * @param string|null $message
     * @param mixed|null $params
     * @param string|null $connectionId
     * @return self
     */
    public static function create(
        int $category,
        ?string $message = null,
        mixed $params = null,
        ?string $connectionId = null
    ): self {
        $log = new self();
        $log->connectionId = $connectionId ?? $log->connectionId;
        $log->category = $category;
        $log->message = $message;
        $log->params = $params;
        $log->store();
        return $log;
    }
}