<?php

namespace Framelix\Framelix\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Cookie;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\SystemValue;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\View;

use function get_class;
use function implode;
use function is_string;
use function str_starts_with;

use const SORT_ASC;

/**
 * Backend sidebar base from which every other backend sidebar must extend
 */
abstract class Sidebar
{
    private static int $order = 0;

    /**
     * Internal link data
     * @var array
     */
    public array $linkData = [];

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'change-cookie') {
            Cookie::set(Request::getGet('cookieName'), $jsCall->parameters['cookieValue'] ?? '', true, 86400);
            Url::getBrowserUrl()->redirect();
        }
    }

    /**
     * Start a group (collapsable)
     * @param string|string[] $label The label, if null then use the page title if a view class as $url is given
     *  Array of labels make first value normal font size and all other labels separate rows with smaller font size
     * @param string $icon The icon
     * @param string|null $badgeText Optional red badge text
     * @param bool $forceOpened The group cannot be closed if this is true
     */
    public function startGroup(
        string|array $label,
        string $icon = "73f",
        ?string $badgeText = null,
        bool $forceOpened = false
    ): void {
        $this->linkData = [
            "type" => "group",
            "label" => $label,
            "links" => [],
            "icon" => $icon,
            "badgeText" => $badgeText,
            "forceOpened" => $forceOpened
        ];
    }

    /**
     * Add a link
     * @param string|Url $url Could be a view class name or a direct URL
     * @param string|string[]|null $label The label, if null then use the page title if a view class as $url is given
     *  Array of labels make first value normal font size and all other labels separate rows with smaller font size
     * @param string|int $icon The icon code (Used by HtmlUtils::getMicronIconTag
     * @param string $target The link target
     * @param array|null $urlParameters Additional url parameters to add to
     * @param array|null $viewUrlParameters Additional view url parameters. Only required when view has a custom url with regex placeholders
     * @param string|null $badgeText Optional red badge text
     */
    public function addLink(
        string|Url $url,
        string|array|null $label = null,
        string|int $icon = 757,
        string $target = "_self",
        ?array $urlParameters = null,
        ?array $viewUrlParameters = null,
        ?string $badgeText = null
    ): void {
        if (!$this->linkData) {
            $this->linkData = [
                "type" => "single",
                "links" => [],
            ];
        }
        $this->linkData["links"][] = [
            "url" => $url,
            "urlParameters" => $urlParameters,
            "viewUrlParameters" => $viewUrlParameters,
            "label" => $label,
            "target" => $target,
            "icon" => $icon,
            "badgeText" => $badgeText
        ];
    }

    /**
     * Get a field that allow the user to select a value which then sets the cookie with the selected value
     * @param string $cookieName The cookie-name key
     * @param array $options The options to be selectable, key is parameter value, value is label
     * @param mixed $defaultValue Default value is cookie is not set
     * @return Select
     */
    public function getCookieSelectorField(string $cookieName, array $options, mixed $defaultValue = null): Select
    {
        $field = new Select();
        $field->name = "cookieValue";
        $field->loadUrlOnChange = JsCall::getUrl(__CLASS__, 'change-cookie', ['cookieName' => $cookieName]);
        $field->loadUrlTarget = "none";
        $field->addOptions($options);
        $field->defaultValue = Cookie::get($cookieName) ?? $defaultValue;
        $field->minWidth = "100%";
        return $field;
    }

    /**
     * Show default sidebar start
     */
    public function showDefaultSidebarStart(): void
    {
        if (Config::$backendLogoFilePath) {
            ?>
            <div class="framelix-sidebar-logo">
                <a href="<?= Config::$backendDefaultView ? View::getUrl(
                    Config::$backendDefaultView
                ) : Url::getApplicationUrl() ?>"><img
                            src="<?= Url::getUrlToPublicFile(Config::$backendLogoFilePath) ?>" alt="App Logo"
                            title="__framelix_backend_startpage__"></a>
            </div>
            <?php
        }
        if (UserToken::getByCookie()->simulatedUser ?? null) {
            ?>
            <framelix-alert theme="warning">
                <div>
                    <?= Lang::get('__framelix_simulateuser_info__', [UserToken::getByCookie()->simulatedUser->email]) ?>
                    <a href="<?= View::getUrl(View\Backend\User\CancelSimulation::class)->setParameter(
                        'redirect',
                        Url::getBrowserUrl()
                    ) ?>"><?= Lang::get('__framelix_simulateuser_cancel__') ?></a>
                </div>
            </framelix-alert>
            <?php
        }
    }

    /**
     * Show default sidebar end
     */
    public function showDefaultSidebarEnd(): void
    {
        $this->startGroup("__framelix_edituser_sidebar_title__", "739");
        $this->addLink(View\Backend\User\Index::class, null, "716");
        $this->addLink(View\Backend\User\Search::class, null, "744");
        $this->showHtmlForLinkData(order: 500);

        // get system values
        $this->startGroup("__framelix_systemvalues__", "787");
        $storableFiles = FileUtils::getFiles(
            FileUtils::getModuleRootPath(FRAMELIX_MODULE) . "/src/Storable/SystemValue",
            "~\.php$~",
            true
        );
        foreach ($storableFiles as $storableFile) {
            $systemValueClass = ClassUtils::getClassNameForFile($storableFile);
            /** @var SystemValue $systemValue */
            $systemValue = new $systemValueClass();
            if ($systemValue->isReadable()) {
                $this->addLink(
                    $systemValue->getDetailsUrl(),
                    ClassUtils::getLangKey($systemValue)
                );
            }
        }
        $this->showHtmlForLinkData(true, 501);

        $this->startGroup("__framelix_view_backend_system__", "788");
        $this->addLink(View\Backend\System\ErrorLogs::class);
        $this->addLink(View\Backend\System\SystemEventLogs::class);
        $this->showHtmlForLinkData(order: 503);

        $this->startGroup("__framelix_developer_options__", "74f");
        $this->addLink(View\Backend\Dev\Update::class, null, "70c");
        $this->addLink(View\Backend\Dev\LangEditor::class, null, "786");
        $this->showHtmlForLinkData(order: 505);

        if (User::get()) {
            $this->addLink(
                View\Backend\UserProfile\Index::class,
                ['__framelix_view_backend_userprofile_index__', User::get()->email],
                "738"
            );
            $this->showHtmlForLinkData(order: 506);
        }
        if (!User::get()) {
            $this->addLink(View\Backend\Login::class, "__framelix_view_backend_login__", "701");
        }
        $this->addLink(View\Backend\Logout::class, "__framelix_logout__", "700");
        $this->showHtmlForLinkData(order: 507);

        $versionInfo = [];
        $versionInfo[] = 'Framelix: ' . Framelix::VERSION;

        echo '<div style="order:99999;font-size: 9px; opacity:0.5; padding-top: 10px; text-align: center">'
            . implode(" | ", $versionInfo)
            . '</div>';
    }

    /**
     * Show html for given link data
     * @param bool $sortByLabel If it is a collapsable then sort entries by label
     * @param int|null $order If not set, it is positioned after the last displayed data
     */
    public function showHtmlForLinkData(bool $sortByLabel = false, ?int $order = null): void
    {
        $order = $order ?? self::$order;
        $linkData = $this->linkData;
        $this->linkData = [];
        $type = $linkData['type'];
        // check if a link is currently the active URL/view in browser
        $activeKey = null;
        $currentUrl = Url::create();
        $currentUrlStr = $currentUrl->getUrlAsString();
        foreach ($linkData['links'] as $key => $row) {
            /** @var Url|string $url */
            $url = $row['url'];
            if (is_string($url)) {
                $viewUrl = View::getUrl($url);
                if (!$viewUrl) {
                    unset($linkData['links'][$key]);
                    continue;
                }
                $row['url'] = $viewUrl;
                $meta = View::getMetadataForView($url);
                if (!User::hasRole(View::replaceAccessRoleParameters($meta['accessRole'], $viewUrl))) {
                    unset($linkData['links'][$key]);
                    continue;
                }
                if ($row['label'] === null) {
                    $linkData['links'][$key]['label'] = View::getTranslatedPageTitle($url, true);
                }
                if (get_class(View::$activeView) === $url) {
                    if (isset($row['urlParameters'])) {
                        $matched = true;
                        foreach ($row['urlParameters'] as $urlParamKey => $urlParamValue) {
                            if ((string)$urlParamValue !== $currentUrl->getParameter($urlParamKey)) {
                                $matched = false;
                                break;
                            }
                        }
                        if ($matched) {
                            $activeKey = $key;
                        }
                    } else {
                        $activeKey = $key;
                    }
                }
            } elseif (str_starts_with($currentUrlStr, $url->getUrlAsString())) {
                $activeKey = $key;
            }
            if (is_array($linkData['links'][$key]['label'] ?? null)) {
                $value = [];
                foreach ($linkData['links'][$key]['label'] as $label) {
                    $value[] = '<div class="framelix-sidebar-label-' . (!$value ? 'default' : 'small') . '">' . Lang::get(
                            $label
                        ) . '</div>';
                }
                $linkData['links'][$key]['label'] = implode($value);
            } elseif (is_string($linkData['links'][$key]['label'] ?? null)) {
                $linkData['links'][$key]['label'] = Lang::get($linkData['links'][$key]['label']);
            }
        }
        if (!$linkData['links']) {
            return;
        }
        $sidebarEntryStart = '<div class="framelix-sidebar-entry" data-type="' . $type . '" style="order:' . $order . '">';
        if ($type === 'group') {
            echo $sidebarEntryStart;
            echo '<div class="framelix-sidebar-collapsable ' . ($activeKey !== null ? 'framelix-sidebar-collapsable-active' : '') . '" data-force-opened="' . (int)$linkData['forceOpened'] . '">';
            if (!$linkData['forceOpened']) {
                echo '<framelix-button raw class="framelix-sidebar-collapsable-title framelix-activate-toggle-handler">';
            } else {
                echo '<div class="framelix-sidebar-collapsable-title">';
            }
            ?>
            <span class="framelix-sidebar-main-icon"><?= HtmlUtils::getFramelixIcon($linkData['icon']) ?></span>
            <span
                    class="framelix-sidebar-label"><?= $linkData['badgeText'] !== null ? '<span class="framelix-sidebar-badge">' . $linkData['badgeText'] . '</span>' : '' ?><?= Lang::get(
                    $linkData['label']
                ) ?></span>
            <?php
            if (!$linkData['forceOpened']) {
                echo '</framelix-button>';
            } else {
                echo '</div>';
            }
            echo '<div class="framelix-sidebar-collapsable-container">';
        }
        if ($sortByLabel) {
            ArrayUtils::sort($linkData['links'], "label", [SORT_ASC]);
        }
        foreach ($linkData['links'] as $key => $row) {
            $url = $row['url'];
            if (is_string($url)) {
                $url = View::getUrl($url, $row['viewUrlParameters'] ?? null);
            }
            if ($row['urlParameters']) {
                $url = clone $url;
                $url->addParameters($row['urlParameters']);
            }
            $url = $url->getUrlAsString();
            if ($type !== 'group') {
                echo $sidebarEntryStart;
            }
            ?>
            <a href="<?= $url ?>" target="<?= $row['target'] ?>"
               class="framelix-sidebar-link <?= $activeKey === $key ? 'framelix-sidebar-link-active' : '' ?>">
                <span class="framelix-sidebar-main-icon"><?= HtmlUtils::getFramelixIcon($row['icon']) ?></span>
                <div
                        class="framelix-sidebar-label"><?= $row['badgeText'] !== null ? '<span class="framelix-sidebar-badge">' . $row['badgeText'] . '</span>' : '' ?><?= $row['label'] ?></div>
            </a>
            <?php
            if ($type !== 'group') {
                echo '</div>';
            }
        }
        if ($type === 'group') {
            echo '</div></div></div>';
        }
        self::$order++;
    }

    /**
     * Show the navigation content
     */
    abstract public function showContent(): void;
}