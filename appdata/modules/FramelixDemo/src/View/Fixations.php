<?php

namespace Framelix\FramelixDemo\View;

use Framelix\Framelix\Date;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use Framelix\FramelixDemo\Storable\Fixation;

class Fixations extends View
{
    protected string|bool $accessRole = "admin,fixation";
    private Fixation $storable;
    private \Framelix\FramelixDemo\StorableMeta\Fixation $meta;

    public function onRequest(): void
    {
        $this->storable = Fixation::getByIdOrNew(Request::getGet('id'));
        $this->meta = new \Framelix\FramelixDemo\StorableMeta\Fixation($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            Fixation::createFixationForRange(
                Date::create(Request::getPost('dateFrom')),
                Date::create(Request::getPost('dateTo'))
            );
            Toast::success('__framelixdemo_view_fixations_created__');
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        switch ($this->tabId) {
            case 'list':
                $this->meta->getTable(Fixation::getByCondition())->show();
                break;
            case 'create':
                $this->meta->getEditForm()->show();
                break;
            default:
                ?>
                <p><?= Lang::get('__framelixdemo_view_fixations_desc__') ?></p>
                <?php
                $tabs = new Tabs();
                $tabs->addTab('list', '__framelixdemo_view_fixations_list__', new self());
                $tabs->addTab('create', '__framelixdemo_view_fixations_create__', new self());
                $tabs->show();
        }
    }
}