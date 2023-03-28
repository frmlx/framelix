<?php

namespace Framelix\FramelixDocs\View;


use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;

use function explode;
use function implode;
use function preg_match;
use function strlen;

abstract class View extends \Framelix\Framelix\View\Backend\View
{
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function getAnchoredTitle(string $id, string $title, string $type = "h1"): string
    {
        return '<' . $type . ' id="anchor-' . $id . '" class="anchor-title"><a class="material-icons" title="Permalink to ' . HtmlUtils::escape('"' . $title . '"') . '" href="#anchor-' . $id . '">link</a><span>' . $title . '</span></' . $type . '>';
    }

    public function getLinkToExternalPage(string $link, ?string $text = null): string
    {
        return '<span class="external-link"><span class="material-icons">open_in_new</span> <a href="' . $link . '" rel="nofollow" target="_blank">' . HtmlUtils::escape($text ?? $link) . '</a></span>';
    }

    public function getLinkToInternalPage(string $class, ?string $overridePageTitle = null): string
    {
        $meta = \Framelix\Framelix\View::getMetadataForView($class);
        return '<a href="' . \Framelix\Framelix\View::getUrl($class) . '" >' . ($overridePageTitle ?? $meta['pageTitle']) . '</a>';
    }

    public function getCodeBlock(string $code, ?string $downloadFilename = null): string
    {
        $lines = explode("\n", $code);
        $firstLine = null;
        $indent = 0;
        foreach ($lines as $key => $line) {
            if (!$firstLine && trim($line)) {
                $firstLine = $line;
                preg_match("~^(\s+)~", $line, $match);
                $indent = strlen($match[1]);
            } elseif (!$firstLine) {
                unset($lines[$key]);
                continue;
            }
            $lines[$key] = mb_substr(rtrim($line), $indent);
        }
        $html = '<div class="code-block"><div class="buttons">';
        $html .= '<framelix-button small theme="transparent" icon="content_paste_go" onclick="FramelixDocs.codeBlockAction(this, \'clipboard\')">Copy to clipboard</framelix-button>';
        if ($downloadFilename) {
            $html .= '<framelix-button small theme="transparent" icon="download" onclick=\'FramelixDocs.codeBlockAction(this, "download", ' . JsonUtils::encode($downloadFilename) . ')\'>Download as file</framelix-button>';
        }

        $html .= '</div><code>' . rtrim(implode("\n", $lines)) . '</code></div>';
        return $html;
    }
}