<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\Config;
use Framelix\Framelix\Console;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\View\Backend\View;
use ReflectionClass;

use function addslashes;
use function call_user_func_array;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function method_exists;
use function preg_replace;
use function property_exists;
use function str_replace;
use function strlen;
use function strtolower;
use function unlink;

class ModuleConfig extends View
{
    protected string|bool $accessRole = "admin";
    private string $module;
    private ?Form $form;

    public static function getEditableForm(string $module): ?Form
    {
        $configClass = "\\Framelix\\$module\\Config";
        if (!class_exists($configClass)) {
            return null;
        }
        if (!method_exists($configClass, "getEditableConfigForm")) {
            return null;
        }
        $form = call_user_func_array([$configClass, "getEditableConfigForm"], []);
        $form->id = "module-" . $module;
        foreach ($form->fields as $field) {
            $langKey = strtolower($field->name);
            $langKey = str_replace("][", "_", $langKey);
            $langKey = preg_replace("~\[(.*?)\]~i", "_$1", $langKey);
            $langKey = "__framelix_config_{$langKey}_";
            $field->label = $field->label ?? $langKey . "label__";
            $field->labelDescription = $field->labelDescription ?? $langKey . "label_desc__";
            if (!Lang::keyExist($field->labelDescription)) {
                $field->labelDescription = null;
            }
            $splitName = ArrayUtils::splitKeyString($field->name);
            $value = $configClass::${$splitName[0]} ?? $field->defaultValue;
            if (is_array($value) && count($splitName) > 1) {
                unset($splitName[0]);
                $value = ArrayUtils::getValue($value, $splitName);
            }
            $field->defaultValue = $value;
        }
        return $form;
    }

    public function onRequest(): void
    {
        if (!isset(Framelix::$registeredModules[Request::getGet('module')])) {
            $this->showInvalidUrlError();
        }
        $this->module = Request::getGet('module');
        $this->form = ModuleConfig::getEditableForm($this->module);
        if (!$this->form) {
            $this->showInvalidUrlError();
        }
        $configClass = "\\Framelix\\" . $this->module . "\\Config";
        if (Form::isFormSubmitted($this->form->id)) {
            $this->form->validate();
            $reflection = new ReflectionClass($configClass);
            $filedata = "<?php\n// this file has been created from backend config interface at " . date(
                    "r"
                ) . " by " . User::get()->email . "\n\n";
            foreach ($this->form->fields as $field) {
                $splitName = ArrayUtils::splitKeyString($field->name);
                if (property_exists($configClass, $splitName[0])) {
                    $property = $reflection->getProperty($splitName[0]);
                    $type = $property->getType();
                    $value = $field->getConvertedSubmittedValue();
                    switch ($type->getName()) {
                        case 'float':
                            if ($type->allowsNull() && !strlen($value)) {
                                $value = 'null';
                            } else {
                                $value = (float)$value;
                            }
                            break;
                        case 'int':
                            if ($type->allowsNull() && !strlen($value)) {
                                $value = 'null';
                            } else {
                                $value = (int)$value;
                            }
                            break;
                        case 'bool':
                            $value = (bool)$value;
                            if ($type->allowsNull() && !$value) {
                                $value = 'null';
                            } else {
                                $value = $value ? 'true' : 'false';
                            }
                            break;
                        case 'array':
                        case 'string':
                            $value = (string)$value;
                            if ($type->allowsNull() && !strlen($value)) {
                                $value = 'null';
                            } else {
                                $value = "'" . addslashes($value) . "'";
                            }
                            break;
                        default:
                            throw new FatalError(
                                'No handler for config flag type "' . $type->getName() . '" at name ' . $field->name
                            );
                    }
                    $varName = $field->name;
                    $varName = str_replace(['[', ']'], ['["', '"]'], $varName);
                    $filedata .= $configClass . "::\$" . $varName . " = $value;\n";
                }
            }
            $filepath = Config::getUserConfigFilePath("02-ui", $this->module);
            $oldData = file_exists($filepath) ? file_get_contents($filepath) : null;
            file_put_contents($filepath, $filedata);
            // wait 3 seconds after change to reflect changes in opcache
            sleep(3);
            // testing if app is still healthy
            $shell = Console::callMethodInSeparateProcess('healthCheck');
            if ($shell->status > 0) {
                Toast::error(
                    Lang::get('__framelix_view_backend_config_error_save__') . '<br/>' . $shell->getOutput(true)
                );
                unlink($filepath);
                if ($oldData) {
                    file_put_contents($filepath, $oldData);
                    sleep(3);
                }
            } else {
                Toast::success('__framelix_saved__');
            }
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $this->form->addSubmitButton();
        $this->form->show();
    }
}