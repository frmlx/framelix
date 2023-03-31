<?php

namespace Framelix\FramelixDocs\View;


use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Network\Cookie;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;

use function array_filter;
use function array_pop;
use function array_values;
use function base64_encode;
use function basename;
use function class_exists;
use function debug_backtrace;
use function explode;
use function file_get_contents;
use function implode;
use function preg_match;
use function rtrim;
use function str_starts_with;
use function strlen;
use function strrpos;
use function substr;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

abstract class View extends \Framelix\Framelix\View\Backend\View
{
    public ?string $clientId = null;
    protected string|bool $accessRole = "*";
    private array $titles = [];
    private ?int $startCodeLineNumber = null;

    public function onRequest(): void
    {
        $this->clientId = Cookie::get('unique-client-id');
        if (!$this->clientId) {
            $this->clientId = RandomGenerator::getRandomString(10, 20);
            Cookie::set('unique-client-id', $this->clientId);
        }

        $this->contentCallable = function () {
            Buffer::start();
            $this->showContent();
            $content = Buffer::get();
            echo '<div class="docs-page">';
            echo '<article class="docs-content">';
            echo $content;
            echo '</article>';

            echo '<nav class="docs-right-nav"><div>';
            foreach ($this->titles as $id => $label) {
                echo '<a href="#anchor-' . $id . '">- ' . $label . '</a>';
            }
            echo '</div></nav>';
            echo '</div>';
        };
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show the source code of the given files
     * @param array $files
     * @return void
     */
    public function showSourceFiles(array $files): void
    {
        $tabs = null;
        if (count($files) > 1) {
            $tabs = new Tabs();
        }
        foreach ($files as $key => $file) {
            if (str_starts_with($file, "Framelix") && class_exists($file)) {
                $file = ClassUtils::getFilePathForClassName($file);
            }
            $codeLanguage = substr($file, strrpos($file, ".") + 1);
            if ($tabs) {
                Buffer::start();
                echo $this->getCodeBlock(file_get_contents($file), $codeLanguage);
                $tabs->addTab($key, basename($file), Buffer::get());
            } else {
                echo $this->getCodeBlock(file_get_contents($file), $codeLanguage);
            }
        }
        $tabs?->show();
    }


    /**
     * Show all code inside this callable without executing it
     * @param callable $callable
     * @return void
     */
    public function startCodeRecording(callable $callable): void
    {
        $this->startCodeLineNumber = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0]['line'];
    }

    /**
     * Displays all code, recored by startCodeRecording() up to the line this code is called
     * @param string|null $codeLanguage
     * @return void
     */
    public function showRecordedCode(?string $codeLanguage = null): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0];
        $endLine = $trace['line'];
        $lines = file($trace['file']);
        $lines = array_filter($lines, function ($lineNr) use ($endLine) {
            $lineNr = $lineNr + 1;
            return $lineNr >= $this->startCodeLineNumber && $lineNr <= $endLine;
        }, ARRAY_FILTER_USE_KEY);
        $lines = array_values($lines);
        unset($lines[0]);
        array_pop($lines);
        echo $this->getCodeBlock(implode($lines), $codeLanguage);
    }

    public function getAnchoredTitle(string $id, string $title, string $type = "h1"): string
    {
        $this->titles[$id] = $title;
        return '<' . $type . ' id="anchor-' . $id . '" class="anchor-title"><a class="material-icons" title="Permalink to ' . HtmlUtils::escape(
                '"' . $title . '"'
            ) . '" href="#anchor-' . $id . '">link</a><span>' . $title . '</span></' . $type . '>';
    }

    public function getLinkToExternalPage(string $link, ?string $text = null): string
    {
        return '<span class="external-link"><span class="material-icons">open_in_new</span> <a href="' . $link . '" rel="nofollow" target="_blank">' . HtmlUtils::escape(
                $text ?? $link
            ) . '</a></span>';
    }

    public function getLinkToInternalPage(string $class, ?string $overridePageTitle = null): string
    {
        $meta = \Framelix\Framelix\View::getMetadataForView($class);
        return '<a href="' . \Framelix\Framelix\View::getUrl(
                $class
            ) . '" >' . ($overridePageTitle ?? $meta['pageTitle']) . '</a>';
    }

    public function getCodeBlock(
        string $code,
        ?string $codeLanguage = null,
        bool $showLineNumbers = true,
        ?string $downloadFilename = null
    ): string {
        $lines = explode("\n", $code);
        $firstLine = null;
        $indent = 0;
        foreach ($lines as $key => $line) {
            if (!$firstLine && trim($line)) {
                $firstLine = $line;
                preg_match("~^(\s+)~", $line, $match);
                $indent = strlen($match[1] ?? '');
            } elseif (!$firstLine) {
                unset($lines[$key]);
                continue;
            }
            $lines[$key] = mb_substr(rtrim($line), $indent);
        }
        $newCode = rtrim(implode("\n", $lines));
        $html = '<div class="code-block" data-originalcode="' . base64_encode($newCode) . '"><div class="buttons">';
        $html .= '<framelix-button small theme="transparent" icon="content_paste_go" onclick="FramelixDocs.codeBlockAction(this, \'clipboard\')">Copy to clipboard</framelix-button>';
        if ($downloadFilename) {
            $html .= '<framelix-button small theme="transparent" icon="download" onclick=\'FramelixDocs.codeBlockAction(this, "download", ' . JsonUtils::encode(
                    $downloadFilename
                ) . ')\'>Download as file</framelix-button>';
        }

        $html .= '</div><pre><code class="' . ($codeLanguage ? 'language-' . $codeLanguage : '') . (!$showLineNumbers ? ' nohljsln' : '') . '">' . HtmlUtils::escape(
                $newCode
            ) . '</code></pre></div>';
        return $html;
    }
}