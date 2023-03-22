<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\Framelix;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Lang;
use Framelix\Framelix\View\Backend\View;

use function strtolower;

class Index extends View
{
    protected string|bool $accessRole = "admin";

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        ?>
        <framelix-alert theme="warning">__framelix_configuration_warning__</framelix-alert>
        <div class="framelix-spacer"></div>
        <?php
        $tabs = new Tabs();
        foreach (Framelix::$registeredModules as $module) {
            $form = ModuleConfig::getEditableForm($module);
            if (!$form) {
                continue;
            }
            $tabs->addTab(
                $module,
                $module === "Framelix" ? "__framelix_configuration_module_pagetitle__" : Lang::get(
                    '__' . strtolower($module) . "_modulename__"
                ),
                new ModuleConfig(),
                ["module" => $module]
            );
        }
        $tabs->addTab("testemail", null, new TestEmail());
        $tabs->show();
    }
}