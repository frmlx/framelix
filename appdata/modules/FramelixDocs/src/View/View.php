<?php

namespace Framelix\FramelixDocs\View;


use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Network\Cookie;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use ReflectionClass;

use function array_filter;
use function array_pop;
use function array_slice;
use function array_values;
use function basename;
use function call_user_func_array;
use function class_exists;
use function count;
use function debug_backtrace;
use function explode;
use function file_get_contents;
use function implode;
use function interface_exists;
use function mb_substr;
use function preg_match;
use function preg_quote;
use function rtrim;
use function str_starts_with;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const FILE_IGNORE_NEW_LINES;
use const FRAMELIX_APPDATA_FOLDER;

abstract class View extends \Framelix\Framelix\View\Backend\View
{
    public ?string $clientId = null;
    protected string|bool $accessRole = "*";
    protected mixed $contentMaxWidth = "1400px";
    private array $titles = [];
    private ?int $startCodeLineNumber = null;
    private array $jsCodeSnippets = [];
    private array $phpCodeSnippets = [];
    private array $sourceFiles = [];

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'show-source') {
            $file = "/framelix/appdata/modules/" . Request::getGet('path');
            $view = new Index();
            $view->addSourceFile($file);
            $view->showSourceFiles();
            ?>
            <script>
              FramelixDocs.renderCodeBlocks()
            </script>
            <?php
        }
        if ($jsCall->action === 'phpCode') {
            $callable = Request::getGet('callable');
            call_user_func_array($callable, []);
        }
    }

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
            $this->showCodeBlock($fileData, $codeLanguage);
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
            $buttonsHtml = '<framelix-button class="run-js-code" theme="primary" icon="touch_app">Run the code bellow</framelix-button>';
            Buffer::start();
            if ($row['description']) {
                echo '<p>' . $row['description'] . '</p>';
            }
            $this->showCodeBlock($row['code'], $codeLanguage, additionalButtonsHtml: $buttonsHtml);
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
     * Add a static PHP method snippet that can be executed by the user in the docs
     * @param callable|array $method (Must be static available and in the current view)
     * @param string $description
     * @param string $scriptLabel
     * @return void
     */
    public function addPhpExecutableMethod(
        callable|array $method,
        string $scriptLabel,
        string $description
    ): void {
        $this->phpCodeSnippets[] = [
            'method' => $method,
            'scriptLabel' => $scriptLabel,
            'description' => $description
        ];
    }

    /**
     * Show the snippets that have been previously collected with ->addPhpExecutableMethod
     * @return void
     */
    public function showPhpExecutableMethodsCodeBlock(): void
    {
        $tabs = null;
        if (count($this->phpCodeSnippets) > 1) {
            $tabs = new Tabs();
        }
        foreach ($this->phpCodeSnippets as $key => $row) {
            $lines = file(ClassUtils::getFilePathForClassName($row['method'][0]), FILE_IGNORE_NEW_LINES);
            $reflection = new ReflectionClass($row['method'][0]);
            $method = $reflection->getMethod($row['method'][1]);
            $code = implode("\n",
                array_slice($lines, $method->getStartLine() + 1, $method->getEndLine() - $method->getStartLine()));
            $codeLanguage = "php";
            $buttonsHtml = '<framelix-button jscall-url="' . JsCall::getUrl(View::class, 'phpCode',
                    ['callable' => $row['method']]) . '" theme="primary" icon="touch_app" target="modal">Run the code bellow</framelix-button>';
            Buffer::start();
            if ($row['description']) {
                echo '<p>' . $row['description'] . '</p>';
            }
            $this->showCodeBlock($code, $codeLanguage, additionalButtonsHtml: $buttonsHtml);
            $contents = Buffer::get();
            if ($tabs) {
                $tabs->addTab($key, $row['scriptLabel'], $contents);
            } else {
                echo $contents;
            }
        }
        $tabs?->show();
        $this->phpCodeSnippets = [];
    }

    /**
     * Start recording all code inside this callable without executing it
     * @param callable $callable
     * @return void
     */
    public function startCodeRecording(callable $callable): void
    {
        $this->startCodeLineNumber = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0]['line'];
    }

    /**
     * Displays all code that recorder started by startCodeRecording() up to the line this code is called
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
        $this->showCodeBlock(implode($lines), $codeLanguage);
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

    public function getLinkToInternalPage(
        string $class,
        ?string $overridePageTitle = null,
        bool $blankWindow = false
    ): string {
        $meta = \Framelix\Framelix\View::getMetadataForView($class);
        return '<a href="' . \Framelix\Framelix\View::getUrl(
                $class
            ) . '" ' . ($blankWindow ? 'target="_blank"' : '') . '>' . ($overridePageTitle ?? $meta['pageTitle']) . '</a>';
    }

    /**
     * For an image, it display an image tag, for other resource, it shows a download link
     * @param string $file
     * @return string
     */
    public function getPublicResourceHtmlTag(string $file): string
    {
        if (!str_starts_with($file, "/")) {
            $file = __DIR__ . "/../../public/" . $file;
        }
        $extension = strtolower(mb_substr($file, strrpos($file, ".") + 1));
        $basename = basename($file);
        $url = Url::getUrlToPublicFile($file);
        if ($extension === "jpg" || $extension === "jpeg" || $extension === "png" || $extension === "webp" || $extension === "svg") {
            return '<div class="docs-image"><img src="' . $url . '" alt="' . $basename . '"></div>';
        }
        return '<span class="external-link"><a href="' . $url . '" download><span class="material-icons">download</span> Download ' . $basename . '</a></span>';
    }

    /**
     * Get tags that are clickable to open file contents in a modal with syntax highlight for direct view
     * @param string[] $files Path is relative starting from /framelix/appdata/modules/$filepath
     * @return string
     */
    public function getShowSourceFileLinkTag(array $files): string
    {
        $tags = [];
        foreach ($files as $relativePath) {
            if (str_starts_with($relativePath,
                    "Framelix") && (class_exists($relativePath) || interface_exists($relativePath))) {
                $relativePath = substr(ClassUtils::getFilePathForClassName($relativePath),
                    strlen(FRAMELIX_APPDATA_FOLDER . "/modules/"));
            }
            $tags[] = '<framelix-button small icon="visibility" theme="transparent" jscall-url="' . JsCall::getUrl(View::class,
                    'show-source',
                    ['path' => $relativePath]) . '" title="Click to show complete source" target="modal">' . $relativePath . '</framelix-button>';
        }
        if (count($tags) > 1) {
            $lastTag = array_pop($tags);
            return implode(", ", $tags) . " and " . $lastTag;
        }
        return implode("", $tags);
    }

    public function showCodeBlock(
        string $code,
        ?string $codeLanguage = null,
        ?string $downloadFilename = null,
        ?string $additionalButtonsHtml = null
    ): void {
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
        echo $html;
    }
}