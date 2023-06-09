<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\StorablePropertyInterface;
use Framelix\Framelix\Db\StorableSchemaProperty;

use function explode;
use function floor;
use function is_numeric;
use function str_contains;
use function str_pad;
use function str_replace;

use const STR_PAD_LEFT;

/**
 * Time utilities for frequent tasks
 */
class Time implements StorablePropertyInterface
{
    /**
     * The time in seconds
     * @var int
     */
    public int $seconds;

    /**
     * Setup the property database schema definition to this storable property itself
     * This defines how the column will be created in the database
     * @param StorableSchemaProperty $storableSchemaProperty
     */
    public static function setupSelfStorableSchemaProperty(StorableSchemaProperty $storableSchemaProperty): void
    {
        $storableSchemaProperty->databaseType = "int";
        $storableSchemaProperty->length = 9;
        $storableSchemaProperty->unsigned = true;
    }

    /**
     * Create an instance from the original database value
     * @param mixed $dbValue
     * @return self
     */
    public static function createFromDbValue(mixed $dbValue): self
    {
        $instance = new self();
        $instance->seconds = is_numeric($dbValue) ? (int)$dbValue : 0;
        return $instance;
    }

    /**
     * Create an instance from a submitted form value
     * @param mixed $formValue
     * @return self
     */
    public static function createFromFormValue(mixed $formValue): self
    {
        return self::create($formValue);
    }

    /**
     * Create instance from given time string
     * @param string $timeString
     * @return self
     */
    public static function create(string $timeString): self
    {
        $instance = new self();
        $instance->seconds = self::toSeconds($timeString);
        return $instance;
    }

    /**
     * Convert any value that contain a time into hours
     * @param mixed $value
     * @return float
     */
    public static function toHours(mixed $value): float
    {
        return round(self::toSeconds($value) / 3600, 4);
    }

    /**
     * Convert any value that contain a time into seconds
     * @param mixed $value
     * @return int
     */
    public static function toSeconds(mixed $value): int
    {
        if ($value instanceof Time) {
            return $value->seconds;
        }
        $value = (string)$value;
        if (!$value) {
            return 0;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        $value = str_replace([",", "."], ":", $value);
        if (!str_contains($value, ":")) {
            return 0;
        }
        $spl = explode(":", $value);
        return ((int)$spl[0] * 3600) + ((int)$spl[1] * 60) + ((int)($spl[2] ?? 0));
    }

    /**
     * Convert hours to time string
     * @param float $hours
     * @param bool $includeSeconds
     * @return string
     */
    public static function hoursToTimeString(float $hours, bool $includeSeconds = false): string
    {
        return self::secondsToTimeString((int)round($hours * 3600), $includeSeconds);
    }

    /**
     * Convert seconds to time string
     * @param int $seconds
     * @param bool $includeSeconds
     * @return string
     */
    public static function secondsToTimeString(int $seconds, bool $includeSeconds = false): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(floor($seconds / 60) % 60);
        $restSeconds = floor($seconds % 60);
        return str_pad((string)$hours, 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string)$minutes, 2, '0', STR_PAD_LEFT)
            . ($includeSeconds ? ':' . str_pad((string)$restSeconds, 2, '0', STR_PAD_LEFT) : '');
    }

    /**
     * Convert to string
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->getDbValue();
    }

    /**
     * Get the database value that is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the property
     * @return float
     */
    public function getDbValue(): float
    {
        return $this->seconds;
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return self::secondsToTimeString($this->seconds);
    }

    /**
     * Get a value that is explicitely used when displayed inside a html table
     * @return string
     */
    public function getHtmlTableValue(): string
    {
        return $this->getHtmlString();
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return self::secondsToTimeString($this->seconds);
    }

    /**
     * Get a value that can be used in sort functions
     * @return int
     */
    public function getSortableValue(): int
    {
        return $this->seconds;
    }

    /**
     * Json serialize
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getRawTextString();
    }
}