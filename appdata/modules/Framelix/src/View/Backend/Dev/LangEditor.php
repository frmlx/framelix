<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;

use function array_keys;
use function array_merge;
use function array_unique;
use function ceil;
use function file_exists;
use function htmlentities;
use function is_string;
use function ksort;
use function nl2br;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

use const SORT_ASC;

class LangEditor extends View
{
    protected bool $devModeOnly = true;

    public function onRequest(): void
    {
        if (Request::getGet('updateFiles')) {
            $count = 0;
            foreach (Framelix::$registeredModules as $module) {
                foreach (Config::$languagesAvailable as $lang) {
                    $langFile = FileUtils::getModuleRootPath($module) . "/lang";
                    $langFile .= "/$lang.json";
                    $existingValues = file_exists($langFile) ? JsonUtils::readFromFile($langFile) : [];
                    if ($existingValues) {
                        ksort($existingValues);
                        JsonUtils::writeToFile($langFile, $existingValues, true);
                        $count++;
                    }
                }
            }
            Toast::success('Updated ' . $count . ' files in ' . count(Framelix::$registeredModules) . " modules");
            $this->getSelfUrl()->redirect();
        }
        if (Request::getBody('framelix-form-button-save')) {
            $lang = Request::getBody('lang');
            $data = Request::getBody('values');
            $dataUnmodified = Request::getBody('valuesUnmodified');
            $modules = array_merge($data ? array_keys($data) : [], $dataUnmodified ? array_keys($dataUnmodified) : []);
            $modules = array_unique($modules);
            // resort all keys of modified modules
            foreach ($modules as $module) {
                $langFile = FileUtils::getModuleRootPath($module) . "/lang";
                $langFile .= "/$lang.json";
                $existingValues = file_exists($langFile) ? JsonUtils::readFromFile($langFile) : [];
                if ($existingValues) {
                    ksort($existingValues);
                    JsonUtils::writeToFile($langFile, $existingValues, true);
                }
            }
            if ($data) {
                foreach ($data as $module => $values) {
                    $langFile = FileUtils::getModuleRootPath($module) . "/lang";
                    $langFile .= "/$lang.json";
                    $existingValues = file_exists($langFile) ? JsonUtils::readFromFile($langFile) : [];
                    foreach ($values as $key => $row) {
                        if (!isset($existingValues[$key])) {
                            $existingValues[$key] = ['', ''];
                        }
                        if (is_string($existingValues[$key])) {
                            $existingValues[$key] = [$existingValues[$key]];
                        }
                        $existingValues[$key][0] = $row[0];
                        if (isset($row[1])) {
                            $existingValues[$key][1] = $row[1];
                        }
                        // remove empty entries
                        if ($lang !== 'en' && $existingValues[$key][0] === '') {
                            unset($existingValues[$key]);
                        }
                    }
                    ksort($existingValues);
                    JsonUtils::writeToFile($langFile, $existingValues, true);
                }
            }
            Toast::success('__framelix_saved__');
            Response::stopWithFormValidationResponse();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if ($this->tabId === 'actions') {
            ?>
            <framelix-button href="<?= $this->getSelfUrl()->setParameter('updateFiles', 1) ?>">
                __framelix_view_backend_dev_langeditor_sort__
            </framelix-button>
            <?php
        } elseif ($this->tabId && str_starts_with($this->tabId, "lang-")) {
            $form = $this->getForm(substr($this->tabId, 5));
            $form->addSubmitButton();
            $form->show();
            ?>
            <script>
              (function () {
                const form = FramelixForm.getById('<?=$form->id?>')
                form.container.on('focusin', 'textarea', function () {
                  if (this.name.startsWith('valuesUnmodified')) {
                    /** @type {FramelixFormFieldHidden} hiddenField */
                    const baseName = this.name.substring(0, this.name.length - 3)
                    const hiddenField = form.fields[baseName + '[1]']
                    if (hiddenField) {
                      hiddenField.input[0].name = hiddenField.input[0].name.replace(/valuesUnmodified/, 'values')
                    }
                    this.name = this.name.replace(/valuesUnmodified/, 'values')
                  }
                })
                form.fields['visibility']?.container.on(FramelixFormField.EVENT_CHANGE_USER, function () {
                  window.location.href = '<?=Url::getBrowserUrl()
                      ->removeParameter('visibility')
                      ->setParameter('visibility', '')->setHash(null)?>' + form.fields['visibility'].getValue()
                })
              })()
            </script>
            <?php
        } else {
            $tabs = new Tabs();
            foreach (Config::$languagesAvailable as $language) {
                $tabs->addTab(
                    'lang-' . $language,
                    $language,
                    new self()
                );
            }
            $tabs->addTab(
                'actions',
                '__framelix_view_backend_dev_langeditor_actions__',
                new self()
            );
            $tabs->show();
        }
    }

    /**
     * Get form
     * @param string $language
     * @return Form
     */
    public function getForm(string $language): Form
    {
        $visibility = Request::getGet('visibility') ?? "untranslated";
        $form = new Form();
        $form->id = "update";
        $form->submitAsyncRaw = true;
        $form->stickyFormButtons = true;

        $field = new Hidden();
        $field->name = 'lang';
        $field->defaultValue = $language;
        $form->addField($field);

        if ($language !== 'en') {
            $field = new Select();
            $field->name = 'visibility';
            $field->label = "Visibility";
            $field->addOption('all', 'All');
            $field->addOption('untranslated', 'Only untranslated');
            $field->defaultValue = $visibility;
            $form->addField($field);

            $fieldTranslated = new Html();
            $fieldTranslated->name = "status";
            $form->addField($fieldTranslated);
        }

        $arr = [];
        foreach (Framelix::$registeredModules as $module) {
            $file = FileUtils::getModuleRootPath($module) . "/lang/$language.json";
            $values = null;
            if (file_exists($file)) {
                $values = JsonUtils::readFromFile($file);
                foreach ($values as $key => $row) {
                    $arr[$key] = [
                        'module' => $module,
                        'value' => $row[0] ?? '',
                        'hash' => $row[1] ?? '',
                        'desc' => Lang::get($key, null, 'en')
                    ];
                }
            }
            // fetch all possible keys
            $keys = [];
            $files = FileUtils::getFiles(FileUtils::getModuleRootPath($module) . "/lang", "~\.json$~", true);
            foreach ($files as $file) {
                $values = JsonUtils::readFromFile($file);
                $keys = array_merge($keys, array_keys($values));
            }
            $keys = array_unique($keys);
            sort($keys);
            foreach ($keys as $key) {
                if (!isset($arr[$key])) {
                    $arr[$key] = [
                        'module' => $module,
                        'value' => '',
                        'hash' => '',
                        'desc' => Lang::get($key, null, 'en')
                    ];
                }
            }
        }
        $totalKeys = 0;
        $translated = 0;
        ksort($arr, SORT_ASC);
        foreach ($arr as $key => $row) {
            $module = $row['module'];
            $totalKeys++;
            $hash = substr(md5($row['desc']), 0, 5);
            $field = new Textarea();
            $field->name = 'valuesUnmodified[' . $module . '][' . $key . '][0]';
            $hashEqual = $hash === $row['hash'] && $row['value'] !== '';
            if ($hashEqual) {
                $translated++;
            }
            $field->label = '';
            if ($language !== 'en') {
                $field->label = '<span style="position:relative; top:2px; color:' . ($hashEqual ? 'var(--color-success-text)' : 'var(--color-error-text)') . '">' . HtmlUtils::getFramelixIcon(
                        ($hashEqual ? '718' : '719')
                    ) . '</span> ';
                $value = $row['desc'];
                if (!str_contains($value, "\n")) {
                    $value = str_replace(["<br/>", "<br />"], "\n", $value);
                }
                $field->labelDescription = nl2br(htmlentities($value));
                if ($hashEqual && $visibility !== 'all') {
                    continue;
                }
            }
            $field->label .= trim($key, "_");
            $field->spellcheck = true;
            $value = $row['value'];
            if (!str_contains($value, "\n")) {
                $value = str_replace(["<br/>", "<br />"], "\n", $value);
            }
            $field->defaultValue = $value;
            $form->addField($field);
            if ($language !== 'en') {
                $field = new Hidden();
                $field->name = 'valuesUnmodified[' . $module . '][' . $key . '][1]';
                $field->defaultValue = $hash;
                $form->addField($field);
            }
        }
        if (isset($fieldTranslated)) {
            $percent = ceil((100 / $totalKeys * $translated));
            $fieldTranslated->labelDescription = "$percent%  - $translated of $totalKeys translated";
            $fieldTranslated->defaultValue = '<progress value="' . ($percent / 100) . '" style="width: 100%"></progress>';
        }

        return $form;
    }
}