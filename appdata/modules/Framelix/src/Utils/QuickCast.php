<?php

namespace Framelix\Framelix\Utils;

use JetBrains\PhpStorm\ExpectedValues;

use function implode;
use function is_array;
use function mb_strlen;

/**
 * Cast primitive values to other primitive values
 */
class QuickCast
{
    /**
     * Cast a value to another value
     * @param mixed $value
     * @param string $to
     * @param bool $recursive If $value is array, then convert array values recursive and return original array structure, otherwise arrays are also casted to target type
     * @param bool $emptyToNull Convert empty values (strlen=0) to null
     * @param string $implodeSeparator When array is casted to string, what separator to us
     * @return mixed
     */
    public static function to(
        mixed $value,
        #[ExpectedValues(values: ['bool', 'int', 'float', 'string', 'array'])] string $to,
        bool $recursive = true,
        bool $emptyToNull = false,
        string $implodeSeparator = ","
    ): mixed {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::to($v, $to, $recursive);
            }
            if (!$recursive) {
                return implode($implodeSeparator, $value);
            }
            return $value;
        }
        switch ($to) {
            case 'array':
                if ($emptyToNull && !mb_strlen((string)$value)) {
                    return null;
                }
                return [$value];
            case 'string':
                $value = (string)$value;
                break;
            case 'float':
                $value = (float)(string)$value;
                break;
            case 'int':
                $value = (int)(string)$value;
                break;
            case 'bool':
                $value = (bool)(string)$value;
                break;
        }
        if ($emptyToNull && !mb_strlen($value)) {
            return null;
        }
        return $value;
    }
}