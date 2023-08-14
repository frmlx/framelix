<?php

namespace Framelix\Framelix\View;

use Framelix\Framelix\Config;
use Framelix\Framelix\ErrorHandler;
use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;
use JetBrains\PhpStorm\ExpectedValues;

use function call_user_func_array;
use function file_exists;
use function get_class;
use function htmlentities;

use const FRAMELIX_MODULE;

abstract class LayoutView extends View
{
    /**
     * Forces screen size to be always that
     * @var string|null
     */
    #[ExpectedValues(values: ['s', 'l'])]
    protected ?string $forceScreenSize = null;

    /**
     * Forces color scheme to be always that
     * @var string|null
     */
    #[ExpectedValues(values: ['light', 'dark'])]
    protected ?string $forceColorScheme = null;

    /**
     * Html to directly output in the <head> section of the page
     * @var string
     */
    protected string $headHtml = '';

    /**
     * Html to directly output in the <head> section of the page but after $headHtml has been included and after page
     * has reached earlyInit stage
     * @var string
     */
    protected string $headHtmlAfterInit = '';

    /**
     * The robots meta tag
     * More information about robots here:
     * https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag
     * @var string
     */
    protected string $metaRobots = 'none';

    /**
     * If set, then use call this function instead of showContent()
     * @var callable|null
     */
    protected $contentCallable = null;

    public function __construct()
    {
        $this->includeDefaultCompilerFileBundles("Framelix");
    }

    /**
     * Add html to <head>
     * @param string $html
     * @return void
     */
    public function addHeadHtml(string $html): void
    {
        $this->headHtml .= "\n" . $html;
    }

    /**
     * Add html to <head> but after $headHtml has been included and after page has reached earlyInit stage
     * @param string $html
     * @return void
     */
    public function addHeadHtmlAfterInit(string $html): void
    {
        $this->headHtmlAfterInit .= "\n" . $html;
    }

    /**
     * Include all compiled bundles for given module that are defined to be $pageAutoInclude=true
     * @param string $module
     */
    public function includeDefaultCompilerFileBundles(string $module): void
    {
        foreach (Config::$compilerFileBundles as $bundle) {
            if ($bundle->module !== $module || !$bundle->pageAutoInclude) {
                continue;
            }
            $this->includeCompiledFileBundle($module, $bundle->type, $bundle->bundleId);
        }
    }

    /**
     * Include a file bundle into page based on given params
     * @param string $module
     * @param string $type js|scss
     * @param string $id
     */
    public function includeCompiledFileBundle(
        string $module,
        string $type,
        string $id
    ): void {
        $bundle = Config::getCompilerFileBundle($module, $type, $id);
        $this->addHeadHtml(HtmlUtils::getIncludeTagForUrl($bundle->getGeneratedBundleUrl()));
    }

    /**
     * Show the default <head> html tag
     */
    public function showDefaultPageStartHtml(): void
    {
        Response::header('X-Robots-Tag: ' . $this->metaRobots);
        $distUrls = [];
        foreach (Config::$compilerFileBundles as $bundle) {
            $file = $bundle->getGeneratedBundleFilePath();
            if (file_exists($file)) {
                $distUrls[$bundle->module][$bundle->type][$bundle->bundleId] = $bundle->getGeneratedBundleUrl();
            }
        }
        $loadableLangFiles = [];
        foreach (Framelix::$registeredModules as $module) {
            foreach (Config::$languagesAvailable as $lang) {
                $files = FileUtils::getFiles(__DIR__ . "/../../../$module/lang", "~/lang/{$lang}[\.-]?.*?\.json$~");
                foreach ($files as $file) {
                    if (!isset($loadableLangFiles[$lang])) {
                        $loadableLangFiles[$lang] = [];
                    }
                    $url = Url::getUrlToPublicFile($file);
                    if ($url) {
                        $loadableLangFiles[$lang][(string)$url] = ['url' => $url];
                    }
                }
                if (isset(Lang::$loadedFiles[$lang])) {
                    foreach (Lang::$loadedFiles[$lang] as $file => $flag) {
                        $url = Url::getUrlToPublicFile($file);
                        if ($url) {
                            $loadableLangFiles[$lang][(string)$url] = ['url' => $url];
                        }
                    }
                }
            }
        }
        ?>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="robots" content="<?= $this->metaRobots ?>">
            <title><?= $this->getPageTitle(true) ?></title>
            <script>
              ;(function () {
                const customElementsSupport = typeof window.customElements !== 'undefined'
                const resizeObserverSupport = typeof ResizeObserver !== 'undefined'
                const lazyLoadingImg = typeof document.createElement('img').loading !== 'undefined'
                const dialogSupport = typeof document.createElement('dialog').open === 'boolean'
                // check for minimal supported browsers, if unsupported then stop any further execution
                // which effectively excludes IE and all legacy edge versions
                // requires at least chrome 77 (2021), firefox 98 (2022) or safari 15.4 (back to iphone 6s/macos 10.15)
                if (!customElementsSupport || !resizeObserverSupport || !lazyLoadingImg || !dialogSupport) {
                  (function showError () {
                    if (!document.body) {
                      setTimeout(showError, 200)
                      return
                    }
                    document.body.innerHTML = '<div style="padding:20px; font-family: Arial, sans-serif; font-size: 24px"><?=Lang::get(
                        '__framelix_browser_unsupported__'
                    )?></div>'
                  })()
                }
              })()
            </script>
            <script>
              class FramelixInit {
                /** @type {function[]} */
                static early = []
                /** @type {function[]} */
                static late = []
                /** @type {Promise} */
                static initialized = null
                /** @type {function} */
                static initializedResolve = null
              }

              FramelixInit.initialized = new Promise(function (resolve) {
                FramelixInit.initializedResolve = resolve
              })
            </script>
            <?= HtmlUtils::getIncludeTagForUrl(
                Config::getCompilerFileBundle(
                    "Framelix",
                    "js",
                    "general-early"
                )->getGeneratedBundleUrl()
            ); ?>
            <script>
              FramelixDeviceDetection.init()
            </script>
            <?= $this->headHtml ?>
            <script>
              FramelixConfig.applicationUrl = <?=JsonUtils::encode(Url::getApplicationUrl())?>;
              FramelixConfig.modulePublicUrl = <?=JsonUtils::encode(
                  Url::getUrlToPublicFile(FileUtils::getModuleRootPath(FRAMELIX_MODULE . "/public"))
              )?>;
              FramelixConfig.compiledFileUrls = <?=JsonUtils::encode($distUrls)?>;
              FramelixConfig.modules = <?=JsonUtils::encode(Framelix::$registeredModules)?>;
              FramelixConfig.dateFormatJs = <?=JsonUtils::encode(Config::$dateFormatJs)?>;
              FramelixConfig.dateFormatPhp = <?=JsonUtils::encode(Config::$dateFormatPhp)?>;
              FramelixConfig.dateTimeFormatJs = <?=JsonUtils::encode(Config::$dateTimeFormatJs)?>;
              FramelixConfig.dateTimeFormatPhp = <?=JsonUtils::encode(Config::$dateTimeFormatPhp)?>;
              FramelixLang.lang = <?=JsonUtils::encode(Config::$language)?>;
              FramelixLang.langFallback = <?=JsonUtils::encode(Config::$languageFallback)?>;
              FramelixLang.languagesAvailable = <?=JsonUtils::encode(Config::$languagesAvailable)?>;
              FramelixLang.loadableLangFiles = <?=JsonUtils::encode($loadableLangFiles)?>;
              FramelixToast.queue = <?=JsonUtils::encode(Toast::getQueueMessages(true))?>;
              Framelix.initEarly()
            </script>
            <?= $this->headHtmlAfterInit ?>
        </head>
        <?php
    }

    /**
     * Get translated page title
     * @param bool $escape Does remove html tags and html escape the string
     * @return string
     */
    public function getPageTitle(bool $escape): string
    {
        return View::getTranslatedPageTitle(get_class($this), $escape, $this->pageTitle);
    }

    /**
     * Show a container where the view gets loaded into that container at the moment it becomes first visible
     */
    public function showAsyncContainer(): void
    {
        PhpToJsData::renderToHtml($this->jsonSerialize());
    }

    /**
     * Show a soft error message without logging an error and stop script execution after that
     * @param string $message
     * @return never
     */
    public function showSoftError(string $message): never
    {
        Buffer::clear();
        $this->contentCallable = function () use ($message) {
            ?>
            <framelix-alert theme="error">
                <?= htmlentities(Lang::get($message)) ?>
            </framelix-alert>
            <?php
        };
        $this->showContentBasedOnRequestType();
        throw new StopExecution();
    }

    /**
     * Show error in case of an exception
     * @param array $logData
     * @return never
     */
    public function onException(array $logData): never
    {
        Buffer::clear();
        $this->contentCallable = function () use ($logData) {
            ErrorHandler::showErrorFromExceptionLog($logData);
        };
        $this->showContentBasedOnRequestType();
        throw new StopExecution();
    }

    /**
     * Show only content data when async, show with layout when not async
     */
    public function showContentBasedOnRequestType(): void
    {
        if (Request::isAsync()) {
            if ($this->contentCallable) {
                call_user_func_array($this->contentCallable, []);
            } else {
                $this->showContent();
            }
            return;
        }
        $this->showContentWithLayout();
    }

    /**
     * Show content
     */
    abstract public function showContent(): void;

    /**
     * Show content with layout
     */
    abstract public function showContentWithLayout(): void;
}