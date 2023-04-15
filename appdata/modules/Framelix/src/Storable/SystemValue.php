<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Url;
use Framelix\Framelix\View;

use function get_class;
use function is_array;
use function is_string;

/**
 * System value
 * @property int $sort
 * @property bool $flagActive
 */
abstract class SystemValue extends StorableExtended
{
    /**
     * Set up the meta with custom fields and information
     * @param \Framelix\Framelix\StorableMeta\SystemValue $meta
     * @return void
     */
    abstract public static function setupStorableMeta(\Framelix\Framelix\StorableMeta\SystemValue $meta): void;

    /**
     * Get entries of the called storable
     * @param mixed|null $additionalEntries Add the entries to the list, used for existing values in a storable
     * @param bool $activeOnly If true, then only list active entries
     * @param string|null $additionalCondition Add another query condition
     * @return static[]
     */
    public static function getEntries(
        mixed $additionalEntries = null,
        bool $activeOnly = true,
        ?string $additionalCondition = null
    ): array {
        $condition = '1';
        if ($activeOnly) {
            $condition = 'flagActive = 1';
        }
        if (is_string($additionalCondition)) {
            $condition .= " && ($additionalCondition)";
        }
        $entries = static::getByCondition($condition, sort: ['+sort']);
        if ($additionalEntries) {
            if (!is_array($additionalEntries)) {
                $additionalEntries = [$additionalEntries];
            }
            foreach ($additionalEntries as $additionalEntry) {
                if ($additionalEntry instanceof static && !isset($entries[$additionalEntry->id])) {
                    $entries[$additionalEntry->id] = $additionalEntry;
                }
            }
        }
        return $entries;
    }

    public function getDetailsUrl(): ?Url
    {
        $storableClassName = get_class($this);
        return View::getUrl(View\Backend\SystemValue::class)?->setParameter('id', $this)->setParameter(
            'type',
            $storableClassName
        );
    }

    public function isReadable(): bool
    {
        return false;
    }

    public function isEditable(): bool
    {
        return false;
    }

    public function isDeletable(): bool
    {
        return false;
    }
}