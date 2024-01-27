<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Utils\ArrayUtils;
use JsonSerializable;

use function array_filter;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function str_contains;
use function str_replace;
use function trim;

class HtmlAttributes implements JsonSerializable
{
    private ?array $styles = null;
    private ?array $classes = null;
    private ?array $other = null;

    /**
     * Create instance from given parameters
     * @param array|null $attributes
     * @param array|null $classes
     * @param array|null $styles
     * @return self
     */
    public static function create(?array $attributes = null, ?array $classes = null, ?array $styles = null): self
    {
        $instance = new self();
        if (is_array($attributes)) {
            $instance->setArray($attributes);
        }
        if (is_array($classes)) {
            $instance->addClass(implode(" ", $classes));
        }
        if (is_array($styles)) {
            $instance->setStyleArray($styles);
        }
        return $instance;
    }

    /**
     * To string
     * Will output the HTML for the given attributes
     * @return string
     */
    public function __toString(): string
    {
        $out = [];
        if ($this->styles) {
            $arr = [];
            foreach ($this->styles as $key => $value) {
                $arr[] = $key . ":$value;";
            }
            $out['style'] = implode(" ", $arr);
        }
        if ($this->classes) {
            $out['class'] = implode(" ", $this->classes);
        }
        if ($this->other) {
            $out = ArrayUtils::merge($out, $this->other);
        }
        $str = [];
        foreach ($out as $key => $value) {
            $str[] = $key . "=" . $this->quotify($value);
        }
        return implode(" ", $str);
    }

    /**
     * Add a class (Multiple separated with empty space)
     * @param string $className
     */
    public function addClass(string $className): void
    {
        if (!$this->classes) {
            $this->classes = [];
        }
        $classes = explode(" ", $className);
        foreach ($classes as $class) {
            $class = trim($class);
            if (!$class) {
                continue;
            }
            if (!in_array($class, $this->classes)) {
                $this->classes[] = $class;
            }
        }
    }

    /**
     * Remove a class (Multiple separated with empty space)
     * @param string $className
     */
    public function removeClass(string $className): void
    {
        if (!$this->classes) {
            return;
        }
        $classes = explode(" ", $className);
        foreach ($classes as $class) {
            $class = trim($class);
            if (!$class) {
                continue;
            }
            $this->classes = array_filter($this->classes, function ($value) use ($class) {
                return $value !== $class;
            });
        }
    }

    /**
     * Set a style attribute
     * @param string $key
     * @param string|null $value Null will delete the style
     */
    public function setStyle(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($this->styles[$key]);
            return;
        }
        $this->styles[$key] = $value;
    }

    /**
     * Set multiple style attributes by given array key/value
     * @param array $values
     */
    public function setStyleArray(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->setStyle($key, $value);
        }
    }

    /**
     * Get a style attribute
     * @param string $key
     * @return string|null
     */
    public function getStyle(string $key): ?string
    {
        return $this->styles[$key] ?? null;
    }

    /**
     * set an attribute
     * @param string $key
     * @param mixed $value Null will delete the attribute
     */
    public function set(string $key, mixed $value): void
    {
        if ($value === null) {
            unset($this->other[$key]);
            return;
        }
        $this->other[$key] = (string)$value;
    }

    /**
     * Set multiple attributes by given array key/value
     * @param array $values
     */
    public function setArray(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get an attribute
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->other[$key] ?? null;
    }

    /**
     * Put quotes around the string, choose ' or " depending on what is not in $str
     * @param string $str
     * @return string
     */
    private function quotify(string $str): string
    {
        $singleQuote = str_contains($str, "'");
        $doubleQuote = str_contains($str, '"');
        // just remove single quotes of both double and single exist
        // this prevents HTML errors
        if ($singleQuote && $doubleQuote) {
            $str = str_replace("'", "", $str);
        }
        if ($doubleQuote) {
            return "'$str'";
        }
        return '"' . $str . '"';
    }

    public function jsonSerialize(): PhpToJsData
    {
        $properties = [];
        foreach ($this as $key => $value) {
            $properties[$key] = $value;
        }
        return new PhpToJsData($properties, $this, 'FramelixHtmlAttributes');
    }
}