<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Backend\Sidebar;
use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\View\LayoutView;

use function call_user_func_array;
use function class_exists;
use function count;
use function get_class;

use const FRAMELIX_MODULE;

abstract class View extends LayoutView
{
    /**
     * The default backend layout
     */
    public const LAYOUT_DEFAULT = 1;

    /**
     * Display the page in a small centered container
     * For login page and such stuff with almost no content
     */
    public const LAYOUT_SMALL_CENTERED = 2;

    /**
     * Initial sidebar status
     * @var bool
     */
    protected bool $hideSidebarInitially = false;

    /**
     * The layout to use
     * @var int
     */
    protected int $layout = self::LAYOUT_DEFAULT;

    protected string|bool $accessRole = true;

    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'settings':
                $form = new Form();
                $form->id = "framelix_user_settings";
                if (count(Config::$languagesAvailable) > 1) {
                    $field = new Select();
                    $field->name = "languageSelect";
                    $field->label = "__framelix_configuration_module_language_pagetitle__";
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

                $field = new Toggle();
                $field->name = "darkMode";
                $field->label = Lang::get('__framelix_darkmode__') . ' <span class="material-icons">dark_mode</span>';
                $form->addField($field);

                $field = new Html();
                $field->name = "resetAlerts";
                $field->defaultValue = '<framelix-button class="framelix_reset_alerts">__framelix_reset_alerts__</framelix-button>';
                $form->addField($field);

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
                        if (v) window.location.href = v
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
                    form.container.on('click', '.framelix_reset_alerts', function () {
                      FramelixCustomElementAlert.resetAllAlerts()
                      FramelixToast.success('__framelix_reset_alerts_done__')
                    })
                  })()
                </script>
                <?php
                break;
        }
    }

    public function showContentWithLayout(): void
    {
        $appIsSetup = Config::doesUserConfigFileExist();
        $this->includeCompiledFileBundle("Framelix", "scss", "backend");
        $this->includeCompiledFileBundle("Framelix", "scss", "backend-fonts");
        $this->includeCompiledFileBundle("Framelix", "js", "backend");
        $this->includeDefaultCompilerFileBundles(FRAMELIX_MODULE);
        $mainSidebarClass = "Framelix\\" . FRAMELIX_MODULE . "\\Backend\\Sidebar";
        $sidebarContent = null;
        if (class_exists($mainSidebarClass)) {
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
        $htmlAttributes = new HtmlAttributes();
        $htmlAttributes->set('data-appstate', $appIsSetup ? 'ok' : 'setup');
        $htmlAttributes->set('data-user', User::get());
        $htmlAttributes->set('data-view', get_class(self::$activeView));
        $htmlAttributes->set('data-navigation', $mainSidebarClass);
        $htmlAttributes->set('data-layout', $this->layout);
        $htmlAttributes->set('data-sidebar-status-initial-hidden', $this->hideSidebarInitially ? '1' : '0');
        if ($this->forceColorScheme) {
            $htmlAttributes->set('data-color-scheme-force', $this->forceColorScheme);
        }
        if ($this->forceScreenSize) {
            $htmlAttributes->set('data-screen-size-force', $this->forceScreenSize);
        }
        if (Config::$backendFaviconFilePath) {
            $this->addHeadHtml(
                '<link rel="icon" href="' . Url::getUrlToPublicFile(Config::$backendFaviconFilePath) . '">'
            );
        }

        Buffer::start();
        echo '<!DOCTYPE html>';
        echo '<html lang="' . Config::$language . '" ' . $htmlAttributes . '>';
        $this->showDefaultPageStartHtml();
        echo '<body>';
        echo '<div class="framelix-page">';
        ?>
        <header class="framelix-top-bar" data-color-scheme='dark'>
            <framelix-button class="framelix-sidebar-toggle" icon="menu" theme="transparent"></framelix-button>
            <h1 class="framelix-page-title"><?= $this->getPageTitle(false) ?></h1>
            <?php
            if ($appIsSetup) {
                ?>
                <framelix-button theme="transparent" class="framelix-user-settings"
                                 jscall-url="<?= JsCall::getUrl(__CLASS__, 'settings') ?>" target="modal"
                                 icon="people"
                                 title="__framelix_backend_user_settings__"></framelix-button>
                <?php
            }
            ?>
        </header>
        <nav class="framelix-sidebar" data-color-scheme='dark'>
            <div class="framelix-sidebar-inner">
                <?= $sidebarContent ?>
            </div>
        </nav>
        <div class="framelix-content">
            <div class="framelix-content-inner">
                <?= $pageContent ?>
            </div>
        </div>
        <script>
          Framelix.initLate()
        </script>
        <?php
        echo '</div>';
        echo '</body></html>';
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
}