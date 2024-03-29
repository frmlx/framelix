<?php

namespace Framelix\Framelix\Dev;

use Framelix\Framelix\Framelix;

use function htmlentities;
use function is_bool;
use function var_dump;
use function var_export;

/**
 * Debug helper for quick dumps'n stuff
 */
class Debug
{
    /**
     * Dump data
     * @param mixed $data
     * @param bool|null $renderAsHtml Force or unforce HTML debug format with console log
     */
    public static function dump(mixed $data, ?bool $renderAsHtml = null): void
    {
        if (is_bool($renderAsHtml) && !$renderAsHtml || $renderAsHtml === null && Framelix::isCli()) {
            var_dump($data);
            return;
        }
        echo '<div class="framelix-debug-data" style="background: #f5f5f5; color:#333; font-family: monospace; font-size: 14px; line-height: 1.2; padding:10px; border:1px solid #aaa;box-shadow: rgba(0,0,0,0.2); margin: 3px; white-space: pre; overflow: auto; max-width: 100vw; box-sizing: border-box">' . htmlentities(
                var_export($data, true)
            ) . '</div>';
    }
}