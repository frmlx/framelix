<?php

namespace Framelix\FramelixDemo\View;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\View\Backend\View;
use Framelix\FramelixDemo\Storable\Invoice;
use Framelix\FramelixDemo\Storable\InvoicePosition;

class InvoicePositions extends View
{
    protected string|bool $accessRole = "admin,invoice-{category}";
    private Invoice $parent;
    private InvoicePosition $storable;
    private \Framelix\FramelixDemo\StorableMeta\InvoicePosition $meta;

    public function onRequest(): void
    {
        $this->parent = Invoice::getByIdOrNew(Request::getGet('id'));
        if (!$this->parent->id) {
            $this->showInvalidUrlError('__framelix_create_or_edit_before_proceed__');
        }
        $this->storable = InvoicePosition::getByIdOrNew(Request::getGet('idPosition'));
        if (!$this->storable->id) {
            $this->storable->invoice = $this->parent;
        }
        $this->meta = new \Framelix\FramelixDemo\StorableMeta\InvoicePosition($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
            if (!$this->storable->id) {
                $positions = $this->parent->getPositions();
                $this->storable->sort = 0;
                if ($positions) {
                    $lastPosition = end($positions);
                    $this->storable->sort = $lastPosition->sort + 1;
                }
            }
            $this->storable->store();
            Toast::success('__framelix_saved__');
            $this->parent->getDetailsUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->meta->getEditForm();
        $form->show();

        $this->meta->getTableWithStorableSorting($this->parent->getPositions())->show();
    }
}