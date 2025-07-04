<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Backend\Sidebar;
use Framelix\Framelix\Config;
use Framelix\Framelix\ErrorHandler;
use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Html\TypeDefs\JsRenderTarget;
use Framelix\Framelix\Html\TypeDefs\JsRequestOptions;
use Framelix\Framelix\Html\TypeDefs\ModalShowOptions;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use JetBrains\PhpStorm\ExpectedValues;

use function call_user_func_array;
use function class_exists;
use function count;
use function get_class;
use function htmlentities;
use function is_file;

use const FRAMELIX_MODULE;

abstract class View extends \Framelix\Framelix\View
{

    /**
     * The default backend layout
     */
    public const int LAYOUT_DEFAULT = 1;

    /**
     * Display the page in a small centered container
     * For login page and such stuff with almost no content
     */
    public const int LAYOUT_SMALL_CENTERED = 2;

    /**
     * The layout to use
     * @var int
     */
    #[ExpectedValues(valuesFromClass: __CLASS__)]
    protected int $layout = self::LAYOUT_DEFAULT;

    /**
     * How much width the content of the layout can take at max
     * If less than 100%, then the content is centered to the screen
     * @var mixed|string
     */
    protected mixed $contentMaxWidth = "100%";

    /**
     * Initial sidebar is closed instead of opened on large screens
     * @var bool
     */
    protected bool $sidebarClosedInitially = false;

    /**
     * Is sidebar enabled
     * @var bool
     */
    protected bool $showSidebar = true;

    /**
     * Is top bar enabled
     * Notice: Without topBar, the sidebar cannot be toggled
     * @var bool
     */
    protected bool $showTopBar = true;

    /**
     * Sidebar position
     * @var string
     */
    #[ExpectedValues(values: ['s', 'l'])]
    protected string $sidebarPosition = "left";

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
     * Html insertions at specific points in layout
     * @var string[]
     */
    protected array $htmlInserts = [];

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

    protected string|bool $accessRole = "admin";

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'settings') {
            $form = new Form();
            $form->id = "framelix_user_settings";
            if (count(Config::$languagesAvailable) > 1) {
                $field = new Select();
                $field->name = "languageSelect";
                $field->label = "__framelix_language__";
                foreach (Config::$languagesAvailable as $supportedLanguage) {
                    $url = Url::getBrowserUrl();
                    $url->replaceLanguage($supportedLanguage);
                    $field->addOption(
                        $url->getUrlAsString(),
                        Lang::ISO_LANG_CODES[$supportedLanguage] ?? $supportedLanguage
                    );
                }
                $field->defaultValue = Url::getBrowserUrl()->getUrlAsString();
                $form->addField($field);
            }

            if (!Request::getGet('forceColorScheme')) {
                $field = new Toggle();
                $field->name = "darkMode";
                $field->label = Lang::get('__framelix_darkmode__') . HtmlUtils::getFramelixIcon('790');
                $form->addField($field);
            }

            $form->show();
            ?>
            <script>
              (async function () {
                const form = FramelixForm.getById('framelix_user_settings')
                await form.rendered
                const languageSelect = FramelixFormField.getFieldByName(FramelixModal.modalsContainer, 'languageSelect')
                if (languageSelect) {
                  languageSelect.container.on(FramelixFormField.EVENT_CHANGE_USER, function () {
                    const v = languageSelect.getValue()
                    if (v) {
                      window.location.href = v
                    }
                  })
                }
                const darkModeToggle = FramelixFormField.getFieldByName(FramelixModal.modalsContainer, 'darkMode')
                if (darkModeToggle) {
                  darkModeToggle.container.on(FramelixFormField.EVENT_CHANGE_USER, function () {
                    FramelixLocalStorage.set('framelix-darkmode', darkModeToggle.getValue() === '1')
                    FramelixDeviceDetection.updateAttributes()
                  })
                  darkModeToggle.setValue(FramelixLocalStorage.get('framelix-darkmode'))
                }
                if (FramelixLocalStorage.get('framelix_alerts_hidden')) {
                  const field = new FramelixFormFieldToggle()
                  field.name = 'resetAlerts'
                  field.defaultValue = '<framelix-button class="framelix_reset_alerts" icon="785">__framelix_reset_alerts__</framelix-button>'
                  form.addField(field)
                  form.render()

                  form.container.on('click', '.framelix_reset_alerts', function () {
                    FramelixCustomElementAlert.resetAllAlerts()
                    FramelixToast.success('__framelix_reset_alerts_done__')
                  })
                }
              })()
            </script>
            <?php
        }
    }

    public function showContentWithLayout(): void
    {
        $appIsSetup = Config::doesUserConfigFileExist();
        $mainSidebarClass = "Framelix\\" . FRAMELIX_MODULE . "\\Backend\\Sidebar";
        $sidebarContent = null;
        if ($this->showSidebar && class_exists($mainSidebarClass)) {
            /** @var Sidebar $sidebarView */
            /** @phpstan-ignore-next-line */
            $sidebarView = new $mainSidebarClass();
            Buffer::start();
            $sidebarView->showDefaultSidebarStart();
            $sidebarView->showContent();
            foreach (Framelix::$registeredModules as $module) {
                if ($module !== FRAMELIX_MODULE && $module !== "Framelix") {
                    $otherSidebarClass = "Framelix\\" . $module . "\\Backend\\Sidebar";
                    if (class_exists($otherSidebarClass)) {
                        /** @var Sidebar $otherSidebarView */
                        $otherSidebarView = new $otherSidebarClass();
                        $otherSidebarView->showContent();
                    }
                }
            }
            $sidebarView->showDefaultSidebarEnd();
            $sidebarContent = Buffer::getAll();
        }
        Buffer::start();
        if ($this->contentCallable) {
            call_user_func_array($this->contentCallable, []);
        } else {
            $this->showContent();
        }
        $pageContent = Buffer::getAll();

        if ($this->metaRobots) {
            Response::header('X-Robots-Tag: ' . $this->metaRobots);
        }

        $htmlAttributes = new HtmlAttributes();
        $htmlAttributes->set('data-appstate', $appIsSetup ? 'ok' : 'setup');
        $htmlAttributes->set('data-user', User::get());
        $htmlAttributes->set('data-sidebar-position', $this->sidebarPosition);
        $htmlAttributes->set('data-show-sidebar', (int)$this->showSidebar);
        $htmlAttributes->set('data-show-topbar', (int)$this->showTopBar);
        $htmlAttributes->set('data-view', get_class(self::$activeView));
        $htmlAttributes->set('data-navigation', $mainSidebarClass);
        $htmlAttributes->set('data-layout', $this->layout);
        $htmlAttributes->set('data-sidebar-status-initial-hidden', $this->sidebarClosedInitially ? '1' : '0');
        if ($this->forceColorScheme) {
            $htmlAttributes->set('data-color-scheme-force', $this->forceColorScheme);
        }
        if ($this->forceScreenSize) {
            $htmlAttributes->set('data-screen-size-force', $this->forceScreenSize);
        }
        if (Config::$backendFaviconFilePath) {
            $this->addHtmlInsert(
                'before-head-close',
                '<link rel="icon" href="' . Url::getUrlToPublicFile(Config::$backendFaviconFilePath) . '">'
            );
        }

        Buffer::start();
        $this->showDefaultPageStartHtml($htmlAttributes);
        echo '<div class="framelix-page" style="--max-content-width:' . $this->contentMaxWidth . '">';
        ?>
        <div class="framelix-page-spacer-left"></div>
        <?php
        if ($this->showSidebar) {
            ?>
            <nav class="framelix-sidebar">
                <div class="framelix-sidebar-inner">
                    <?= $sidebarContent ?>
                </div>
            </nav>
            <?php
        }
        ?>
        <div class="framelix-content">
            <?php
            if ($this->showTopBar) {
                ?>
                <header class="framelix-top-bar">
                    <?php
                    if ($this->showSidebar) {
                        ?>
                        <framelix-button class="framelix-sidebar-toggle" icon="73f"
                                         theme="transparent"></framelix-button>
                        <?php
                    }
                    ?>
                    <h1 class="framelix-page-title"><?= $this->getPageTitle(false) ?></h1>
                    <?php
                    if ($appIsSetup) {
                        ?>
                        <framelix-button theme="transparent"
                                         class="framelix-user-settings"
                            <?= new JsRequestOptions(JsCall::getSignedUrl([$this::class, 'onJsCall'], 'settings', ['forceColorScheme' => $this->forceColorScheme]),
                                new JsRenderTarget(modalOptions: new ModalShowOptions(maxWidth: 500)))->toDefaultAttrStr() ?>
                                         icon="739"
                                         title="__framelix_backend_user_settings__"></framelix-button>
                        <?php
                    }
                    ?>
                </header>
                <?php
            }
            ?>
            <div class="framelix-content-inner">
                <div class="framelix-content-spacer-left"></div>
                <div class="framelix-content-inner-inner">
                    <?= $pageContent ?>
                </div>
                <div class="framelix-content-spacer-right"></div>
            </div>
        </div>
        <div class="framelix-page-spacer-right"></div>
        <?php
        echo '</div>';
        $this->showDefaultPageEndHtml();
        echo Buffer::getAll();
    }

    /**
     * On access denied, redirect to login page if not already logged in
     * @return never
     */
    public function showAccessDenied(): never
    {
        if (!User::get() && !(View::$activeView instanceof Login)) {
            \Framelix\Framelix\View::getUrl(Login::class)->setParameter('redirect', (string)Url::create())->redirect();
        }
        parent::showAccessDenied();
    }

    /**
     * Add additional html to be displayed in certain positions on the page
     * @param string $position
     * @param string $html
     */
    public function addHtmlInsert(
        #[ExpectedValues(values: [
            'before-head-close',
            'after-head-open',
            'before-body-close',
            'after-body-open'
        ])] string $position,
        string $html
    ): void {
        if (!isset($this->htmlInserts[$position])) {
            $this->htmlInserts[$position] = "";
        }
        $this->htmlInserts[$position] .= $html . "\n";
    }

    /**
     * Show the default html, including doctype, <head> and starting <body> tag
     * @param HtmlAttributes|null $htmlAttributes
     * @param HtmlAttributes|null $bodyAttributes
     * @return void
     */
    public function showDefaultPageStartHtml(
        ?HtmlAttributes $htmlAttributes = null,
        ?HtmlAttributes $bodyAttributes = null
    ): void {
        $distUrls = [];
        foreach (Config::$compilerFileBundles as $bundle) {
            if (is_file($bundle->getGeneratedBundleFilePath())) {
                $distUrls[$bundle->module][$bundle->type][$bundle->bundleId] = $bundle->getGeneratedBundleUrl();
            }
        }
        $includedBundles = [];
        $positions = ['early', 'default'];
        foreach ($positions as $position) {
            foreach (Config::getMatchingCompilerFileBundles($this, $position) as $key => $bundle) {
                $includedBundles[$position][$key] = $bundle;
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

        echo '<!DOCTYPE html>';
        echo '<html lang="' . Config::$language . '" ' . $htmlAttributes . '>';
        ?>
        <head>
            <?= $this->htmlInserts['after-head-open'] ?? '' ?>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <?php
            if ($this->metaRobots) {
                echo '<meta name="robots" content="' . $this->metaRobots . '">';
            }
            ?>
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
            <?= HtmlUtils::getIncludeTagsForBundles($includedBundles['early'] ?? []); ?>
            <script>
              FramelixDeviceDetection.init()
            </script>
            <?= HtmlUtils::getIncludeTagsForBundles($includedBundles['default'] ?? []); ?>
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
            <?= $this->htmlInserts['before-head-close'] ?? '' ?>
        </head>
        <?php
        echo '<body ' . $bodyAttributes . '>';
        echo $this->htmlInserts['after-body-open'] ?? '';
    }

    /**
     * Show the the late init scripts end </body></html> tags
     * @return void
     */
    public function showDefaultPageEndHtml(): void
    {
        $includedBundles = [];
        $positions = ['late'];
        foreach ($positions as $position) {
            foreach (Config::getMatchingCompilerFileBundles($this, $position) as $key => $bundle) {
                $includedBundles[$position][$key] = $bundle;
            }
        }
        echo HtmlUtils::getIncludeTagsForBundles($includedBundles['late'] ?? []);
        echo '<script>Framelix.initLate()</script>';
        echo $this->htmlInserts['before-body-close'] ?? '';
        echo '</body></html>';
    }

    /**
     * Get translated page title
     * @param bool $escape Does remove html tags and html escape the string
     * @return string
     */
    public function getPageTitle(bool $escape): string
    {
        return \Framelix\Framelix\View::getTranslatedPageTitle(get_class($this), $escape, $this->pageTitle);
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
     * Show page content
     */
    abstract public function showContent(): void;

}