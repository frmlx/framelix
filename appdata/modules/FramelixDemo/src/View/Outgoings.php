<?php

namespace Framelix\FramelixDemo\View;

use Framelix\FramelixDemo\Storable\Outgoing;
use Framelix\Framelix\Date;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\View\Backend\View;

use function date;

class Outgoings extends View
{
    protected string|bool $accessRole = "admin,outgoing";
    private Outgoing $storable;
    private \Framelix\FramelixDemo\StorableMeta\Outgoing $meta;

    public function onRequest(): void
    {
        if ($id = Request::getGet('fromDepreciation')) {
            $depriciation = \Framelix\FramelixDemo\Storable\Depreciation::getById($id);
            if ($depriciation) {
                $netSplit = $depriciation->netSplit;
                $year = (int)date("Y");
                foreach ($netSplit as $row) {
                    if ($row['year'] === $year && !isset($row['outgoing'])) {
                        $outgoing = new Outgoing();
                        $outgoing->depreciation = $depriciation;
                        $outgoing->outgoingCategory = $depriciation->outgoingCategory;
                        $outgoing->comment = $depriciation->comment;
                        $outgoing->date = Date::create("$year-12-31");
                        $outgoing->net = (float)$row['value'];
                        $outgoing->store();
                        Toast::success('__framelixdemo_storable_outgoing_created_from_depreciation__');
                        $outgoing->getDetailsUrl()->redirect();
                    }
                }
            }
        }
        $this->storable = Outgoing::getByIdOrNew(Request::getGet('id'));
        if (!$this->storable->id) {
            $this->storable->date = Date::create('now');
        }
        $this->meta = new \Framelix\FramelixDemo\StorableMeta\Outgoing($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
            $this->storable->store();
            $form->store($this->storable);
            Toast::success('__framelix_saved__');
            $this->getSelfUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->meta->getEditForm();
        $form->show();

        $this->meta->showSearchAndTableInTabs(Outgoing::getOpenEntries());
    }
}