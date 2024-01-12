<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Html\CompilerFileBundle;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Url;

use function html_entity_decode;
use function htmlentities;
use function is_array;
use function nl2br;
use function str_ends_with;

/**
 * Html utilities for frequent tasks
 */
class HtmlUtils
{
    /**
     * Display html tag to display a framelix icon
     * @param int|string $codePoint Icon list can be found at Framelix/node_modules/microns/icons.json or at link see bellow
     * @param HtmlAttributes|array|null $attributes
     * @return string
     * @link https://www.s-ings.com/projects/microns-icon-font/
     */
    public static function getFramelixIcon(
        int|string $codePoint,
        HtmlAttributes|array|null $attributes = null
    ): string {
        if (is_array($attributes)) {
            $attributes = HtmlAttributes::create($attributes);
        }
        $attributes?->addClass('framelix-icon');
        return '<framelix-icon ' . ($attributes ? (string)$attributes : '') . ' icon="' . $codePoint . '">&#xe' . $codePoint . ';</framelix-icon>';
    }

    /**
     * Get include tags for given compiler bundles
     * @param CompilerFileBundle[] $bundles
     * @return string
     */
    public static function getIncludeTagsForBundles(array $bundles): string
    {
        $html = '';
        foreach ($bundles as $bundle) {
            $html .= self::getIncludeTagForBundle($bundle) . "\n";
        }
        return $html;
    }

    /**
     * Get include tag for given compiler bundle
     * @param CompilerFileBundle $bundle
     * @return string
     */
    public static function getIncludeTagForBundle(CompilerFileBundle $bundle): string
    {
        return self::getIncludeTagForUrl($bundle->getGeneratedBundleUrl());
    }

    /**
     * Get include tag for given url
     * @param Url $url
     * @return string
     */
    public static function getIncludeTagForUrl(
        Url $url
    ): string {
        if (str_ends_with($url->urlData['path'], ".css")) {
            return '<link rel="stylesheet" media="all" href="' . $url . '">';
        } elseif (str_ends_with($url->urlData['path'], ".js")) {
            return '<script src="' . $url . '"></script>';
        } else {
            throw new FatalError(
                "Cannot generate include tag for  $url - Unsupported extension"
            );
        }
    }

    /**
     * Escape a given string to be safe for user inputs
     * @param mixed $str
     * @param bool $nl2br Line feeds to <br/>
     * @return string
     */
    public static function escape(mixed $str, bool $nl2br = false): string
    {
        $str = htmlentities($str, encoding: 'UTF-8');
        if ($nl2br) {
            $str = nl2br($str);
        }
        return $str;
    }

    /**
     * Unescape a given string to raw text instead of html (opposite of escape())
     * @param mixed $str
     * @return string
     */
    public static function unescape(mixed $str): string
    {
        return html_entity_decode($str, encoding: 'UTF-8');
    }
}