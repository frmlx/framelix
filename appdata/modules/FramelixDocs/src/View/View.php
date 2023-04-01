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
use function basename;
use function class_exists;
use function count;
use function debug_backtrace;
use function explode;
use function file_get_contents;
use function implode;
use function interface_exists;
use function preg_match;
use function preg_quote;
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
    protected mixed $contentMaxWidth = "1400px";
    private array $titles = [];
    private ?int $startCodeLineNumber = null;
    private array $jsCodeSnippets = [];
    private array $sourceFiles = [];

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
     * @param string $filePathOrClass
     * @param string|null $docsId If set, only parses the lines between // docs-id-start: $docsId and  docs-id-end: $docsId
     * @return void
     */
    public function addSourceFile(string $filePathOrClass, ?string $docsId = null): void
    {
        $this->sourceFiles[] = [
            'filePathOrClass' => $filePathOrClass,
            'docsId' => $docsId
        ];
    }

    /**
     * Show the source files previously added with addSourceFile
     * @return void
     */
    public function showSourceFiles(): void
    {
        $tabs = null;
        if (count($this->sourceFiles) > 1) {
            $tabs = new Tabs();
        }
        foreach ($this->sourceFiles as $key => $row) {
            $file = $row['filePathOrClass'];
            if (str_starts_with($file, "Framelix") && (class_exists($file) || interface_exists($file))) {
                $file = ClassUtils::getFilePathForClassName($file);
            }
            $fileData = file_get_contents($file);
            if ($row['docsId']) {
                $id = preg_quote($row['docsId'], "~");
                preg_match("~\/\/\s*docs-id-start:\s*\b$id\n*(.*?)\/\/\s*docs-id-end:\s*\b$id~is", $fileData, $match);
                if ($match) {
                    $fileData = $match[1];
                }
            }
            $codeLanguage = substr($file, strrpos($file, ".") + 1);
            Buffer::start();
            echo $this->getCodeBlock($fileData, $codeLanguage);
            $contents = Buffer::get();
            if ($tabs) {
                $tabs->addTab($key, basename($file), $contents);
            } else {
                echo $contents;
            }
        }
        $tabs?->show();
    }

    /**
     * Add a JS code snippet that can be executed by the user in the docs
     * @param string $scriptLabel
     * @param string $description
     * @param string $code
     * @return void
     */
    public function addJsExecutableSnippet(
        string $scriptLabel,
        string $description,
        string $code
    ): void {
        $this->jsCodeSnippets[] = [
            'scriptLabel' => $scriptLabel,
            'description' => $description,
            'code' => $code
        ];
    }

    /**
     * Show the snippets that have been previously collected with ->addJsExecutableSnippet
     * @return void
     */
    public function showJsExecutableSnippetsCodeBlock(): void
    {
        $tabs = null;
        if (count($this->jsCodeSnippets) > 1) {
            $tabs = new Tabs();
        }
        foreach ($this->jsCodeSnippets as $key => $row) {
            $codeLanguage = "js";
            $buttonsHtml = '<framelix-button theme="primary" icon="touch_app" onclick="FramelixDocs.runJsCode(this)">Run the code bellow</framelix-button>';
            Buffer::start();
            if ($row['description']) {
                echo '<p>' . $row['description'] . '</p>';
            }
            echo $this->getCodeBlock($row['code'], $codeLanguage, additionalButtonsHtml: $buttonsHtml);
            $contents = Buffer::get();
            if ($tabs) {
                $tabs->addTab($key, $row['scriptLabel'], $contents);
            } else {
                echo $contents;
            }
        }
        $tabs?->show();
        $this->jsCodeSnippets = [];
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
        ?string $downloadFilename = null,
        ?string $additionalButtonsHtml = null
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
        $html = '<div class="code-block"><div class="buttons">' . $additionalButtonsHtml;
        $html .= '<framelix-button small theme="transparent" icon="content_paste_go" onclick="FramelixDocs.codeBlockAction(this, \'clipboard\')">Copy to clipboard</framelix-button>';
        if ($downloadFilename) {
            $html .= '<framelix-button small theme="transparent" icon="download" onclick=\'FramelixDocs.codeBlockAction(this, "download", ' . JsonUtils::encode(
                    $downloadFilename
                ) . ')\'>Download as file</framelix-button>';
        }

        $html .= '</div><pre><code class="' . ($codeLanguage ? 'language-' . $codeLanguage : '') . '"></code></pre></div><script type="application/json">' . JsonUtils::encode(
                $newCode
            ) . '</script>';
        return $html;
    }
}