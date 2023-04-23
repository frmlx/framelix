<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Db\StorableSchema;

/**
 * A storable helper that allow to quickly save an array of values attached to another storable
 * @property Storable $parent The parent Storable this array is stored to
 * @property string $key The array key
 * @property Storable|null $valueStorable A storable, stored as integer reference
 * @property mixed|null $value Any other value then Storable (Stored as json)
 */
class StorableArray extends StorableExtended
{
    private static array $cache = [];

    /**
     * Get all StorableArray objects to this parent
     * @param Storable $parent
     * @param bool $clearCache By default, this getter is cached once it has been used for a given parent
     * @return static[]
     */
    public static function getForParent(Storable $parent, bool $clearCache = false): array
    {
        if ($clearCache) {
            unset(self::$cache[static::class][$parent->id]["objects"]);
        } elseif (isset(self::$cache[static::class][$parent->id]["objects"])) {
            return self::$cache[static::class][$parent->id]["objects"];
        }
        $objects = static::getByCondition('parent = {0}', [$parent]);
        self::$cache[static::class][$parent->id]["objects"] = $objects;
        return $objects;
    }

    /**
     * Get the array as it was stored with key/value for given parent
     * @param Storable $parent
     * @param bool $clearCache By default, this getter is cached once it has been used for a given parent
     * @return array
     */
    public static function getValues(Storable $parent, bool $clearCache = false): array
    {
        if ($clearCache) {
            unset(self::$cache[static::class][$parent->id]["getValues"]);
        } elseif (isset(self::$cache[static::class][$parent->id]["getValues"])) {
            return self::$cache[static::class][$parent->id]["getValues"];
        }
        $objects = static::getForParent($parent);
        $arr = [];
        foreach ($objects as $object) {
            $arr[$object->key] = $object->valueStorable ?? $object->value;
        }
        self::$cache[static::class][$parent->id]["getValues"] = $arr;
        return $arr;
    }

    /**
     * Get a single value out of a stored array
     * @param Storable $parent
     * @param string $key
     * @param bool $clearCache By default, this getter is cached once it has been used for a given parent
     * @return mixed Null if key was not found
     */
    public static function getValue(Storable $parent, string $key, bool $clearCache = false): mixed
    {
        return static::getValues($parent, $clearCache)[$key];
    }

    /**
     * Set values by given array, will override existing values and delete keys that are not given anymore
     * @param Storable $parent
     * @param array $values
     * @return StorableArray[] The newly created and updated StorableArray entries
     */
    public static function setValues(Storable $parent, array $values): array
    {
        $objectsExists = static::getForParent($parent);
        $arrNew = [];
        foreach ($values as $key => $value) {
            $objectNew = self::setValue($parent, $key, $value);
            if ($objectNew) {
                $arrNew[$objectNew->key] = $objectNew;
            }
        }
        // delete the rest that previously existed and now not anymore
        foreach ($objectsExists as $objectExist) {
            if (!isset($arrNew[$objectExist->key])) {
                $objectExist->delete();
            }
        }
        return $arrNew;
    }

    /**
     * Set value with given key. If value is null, the key will be deleted
     * @param Storable $parent
     * @param string $key
     * @param mixed $value Null will delete the value if exist in database
     * @return StorableArray|null
     */
    public static function setValue(Storable $parent, string $key, mixed $value): ?StorableArray
    {
        unset(self::$cache[static::class][$parent->id]);
        $objectExist = static::getByConditionOne('parent = {0} && `key` = {1}', [$parent, $key]);
        if ($value === null) {
            $objectExist?->delete();
            return null;
        }
        $object = $objectExist ?? new static();
        $object->parent = $parent;
        $object->key = $key;
        $object->valueStorable = null;
        $object->value = null;
        if ($value instanceof Storable) {
            $object->valueStorable = $value;
        } else {
            $object->value = $value;
        }
        $object->store();
        return $object;
    }

    /**
     * Delete all entries to a given parent
     * @param Storable $parent
     * @return int The number of deleted entries
     */
    public static function deleteValues(Storable $parent): int
    {
        unset(self::$cache[static::class][$parent->id]);
        $objects = static::getForParent($parent);
        self::deleteMultiple($objects);
        return count($objects);
    }

    /**
     * Delete a value with a specific key
     * @param Storable $parent
     * @param string $key
     * @return bool True if key existed
     */
    public static function deleteValue(Storable $parent, string $key): bool
    {
        $objectExist = static::getByConditionOne('parent = {0} && `key` = {1}', [$parent, $key]);
        if ($objectExist) {
            unset(self::$cache[static::class][$parent->id]);
            $objectExist->delete();
            return true;
        }
        return false;
    }

    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->addIndex(['parent', 'key'], 'unique');
    }
}