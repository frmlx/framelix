<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ClassUtils;

use function call_user_func_array;
use function class_exists;
use function is_string;

class SystemValue extends View
{
    protected \Framelix\Framelix\Storable\SystemValue $storableIntern;
    protected \Framelix\Framelix\StorableMeta\SystemValue $metaIntern;

    public function onRequest(): void
    {
        $systemValueType = Request::getGet('type');
        if (!is_string($systemValueType) || !class_exists($systemValueType)) {
            $this->showSoftError('Given type not exist');
        }

        $this->storableIntern = new $systemValueType();
        if (!$this->storableIntern->isReadable()) {
            $this->showSoftError("Access denied. isReadable must return true in order to access this page.");
        }
        $this->pageTitle = ClassUtils::getLangKey($this->storableIntern);
        $this->storableIntern = call_user_func_array(
            [$systemValueType, "getByIdOrNew"],
            [Request::getGet('id')]
        );
        $this->metaIntern = new  \Framelix\Framelix\StorableMeta\SystemValue($this->storableIntern);
        if (!$this->storableIntern->id) {
            $this->storableIntern->flagActive = true;
        }

        if (Form::isFormSubmitted($this->metaIntern->getEditFormId())) {
            $form = $this->metaIntern->getEditForm();
            $form->validate();
            if (!$this->storableIntern->id) {
                $nextSort = $this->storableIntern::getByConditionOne(sort: ["-sort"])->sort ?? 0;
                $this->storableIntern->sort = $nextSort + 1;
            }
            $form->setStorableValues($this->storableIntern);
            $this->storableIntern->store();
            $form->store($this->storableIntern);

            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->removeParameterByValue($this->storableIntern)->redirect();
        }

        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        switch ($this->tabId) {
            case 'active':
            case 'inactive':
                $objects = $this->storableIntern::getByCondition(
                    'flagActive = {0}',
                    [$this->tabId === 'active'],
                    ['+sort']
                );
                $table = $this->metaIntern->getTableWithStorableSorting($objects, $this->tabId);
                $table->show();
                break;
            default:
                $form = $this->metaIntern->getEditForm();
                $form->show();
                $counts = Sql::get()->fetchColumn(
                    "
                    SELECT COUNT(*), flagActive
                    FROM `" . $this->storableIntern::class . "`
                    GROUP BY flagActive
                "
                );
                if ($counts) {
                    ?>
                    <div class="framelix-spacer-x2"></div>
                    <?php
                    $tabs = new Tabs();
                    $tabs->addTab(
                        'active',
                        Lang::get('__framelix_systemvalues_active__') . " (" . ($counts[1] ?? 0) . ")",
                        new static()
                    );
                    if ($counts[0] ?? 0) {
                        $tabs->addTab(
                            'inactive',
                            Lang::get('__framelix_systemvalues_inactive__') . " (" . ($counts[0]) . ")",
                            new static()
                        );
                    }
                    $tabs->show();
                }
        }
    }
}