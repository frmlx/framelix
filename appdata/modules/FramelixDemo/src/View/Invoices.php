<?php

namespace Framelix\FramelixDemo\View;

use Framelix\FramelixDemo\Storable\Invoice;
use Framelix\Framelix\Date;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\View\Backend\View;

use function var_dump;

class Invoices extends View
{
    protected string|bool $accessRole = "admin,invoice-{category}";
    private Invoice $storable;
    private \Framelix\FramelixDemo\StorableMeta\Invoice $meta;
    private int $category;


    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'invoice-pdf-download':
                $invoice = Invoice::getById(Request::getGet('invoice'));
                if (!$invoice) {
                    return;
                }
                $form = new Form();
                $form->submitUrl = \Framelix\Framelix\View::getUrl(InvoicePdf::class)
                    ->setParameter('category', $invoice->category)
                    ->setParameter('id', $invoice);
                $form->id = "download";

                $field = new Select();
                $field->name = 'type';
                $field->chooseOptionLabel = '__framelixdemo_storable_invoice_download_label__';
                $field->required = true;
                if (!$invoice->attachment) {
                    $field->addOption('preview', '__framelixdemo_storable_invoice_download_preview__');
                    $field->addOption('original', '__framelixdemo_storable_invoice_download_original__');
                } else {
                    $field->addOption('original', '__framelixdemo_storable_invoice_download_original__');
                    if ($invoice->category === Invoice::CATEGORY_INVOICE) {
                        $field->addOption('copy', '__framelixdemo_storable_invoice_download_copy__');
                        $field->addOption('custom', '__framelixdemo_storable_invoice_download_custom__');
                    }
                }
                if ($copies = $invoice->getInvoiceCopies()) {
                    foreach ($copies as $key => $invoiceCopy) {
                        $field->addOption(
                            $key,
                            $invoiceCopy->filename . " (" . $invoiceCopy->createTime->getHtmlString() . ")"
                        );
                    }
                }
                $form->addField($field);

                $field = new Text();
                $field->name = 'title';
                $field->label = '__framelixdemo_storable_invoice_title_label__';
                $field->required = true;
                $field->getVisibilityCondition()->equal('type', 'custom');
                $form->addField($field);

                $field = new Textarea();
                $field->name = 'textBeforePosition';
                $field->label = '__framelixdemo_storable_invoice_textbeforeposition_label__';
                $field->getVisibilityCondition()->equal('type', 'custom');
                $form->addField($field);

                $field = new Textarea();
                $field->name = 'textAfterPosition';
                $field->label = '__framelixdemo_storable_invoice_textafterposition_label__';
                $field->getVisibilityCondition()->equal('type', 'custom');
                $form->addField($field);

                $form->addSubmitButton('pdf-download', '__framelixdemo_storable_invoice_download__', 'picture_as_pdf');
                $form->executeAfterAsyncSubmit = /** @lang JavaScript */
                    'await FramelixModal.destroyAll()';
                $form->show();
                break;
        }
    }

    public function onRequest(): void
    {
        $this->category = (int)Request::getGet('category');
        if (Request::getGet('copy')) {
            $copyFrom = Invoice::getById(Request::getGet('copy'));
            if ($copyFrom->category ?? null === $this->category) {
                $clone = new Invoice();
                $clone->category = $this->category;
                $clone->date = Date::create('now');
                $clone->performancePeriod = $copyFrom->performancePeriod;
                $clone->net = $copyFrom->net;
                $clone->incomeCategory = $copyFrom->incomeCategory;
                $clone->creator = $copyFrom->creator;
                $clone->receiverVatId = $copyFrom->receiverVatId;
                $clone->receiver = $copyFrom->receiver;
                $clone->textBeforePosition = $copyFrom->textBeforePosition;
                $clone->textAfterPosition = $copyFrom->textAfterPosition;
                $clone->bankData = $copyFrom->bankData;
                $clone->store();
                $positions = $copyFrom->getPositions();
                foreach ($positions as $position) {
                    $positionClone = $position->clone();
                    $positionClone->invoice = $clone;
                    $positionClone->sort = (int)$position->sort;
                    $positionClone->store();
                }
                Toast::success('__framelixdemo_storable_invoice_copied__');
                $clone->getDetailsUrl()->redirect();
            }
        }
        $this->pageTitle = '__framelixdemo_view_invoice_category_' . $this->category . '__';
        $this->storable = Invoice::getByIdOrNew(Request::getGet('id'));
        if ($this->storable->id && $this->storable->category !== $this->category) {
            $this->storable = new Invoice();
        }
        if (!$this->storable->id) {
            $this->storable->category = $this->category;
            $this->storable->date = Date::create('now');
        } else {
            $this->pageTitle = $this->storable->getRawTextString();
        }

        $this->meta = new \Framelix\FramelixDemo\StorableMeta\Invoice($this->storable);
        $this->meta->parameters['category'] = $this->storable->category;
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
            $this->storable->store();

            if (Request::getPost('deleteInvoice') && $this->storable->attachment) {
                $this->storable->attachment->delete();
            }
            Toast::success('__framelix_saved__');
            $this->storable->getDetailsUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        switch ($this->tabId) {
            case 'create':
                $form = $this->meta->getEditForm();
                $form->show();
                $this->meta->showSearchAndTableInTabs(Invoice::getOpenEntries($this->category));
                break;
            default:
                $tabs = new Tabs();
                $tabs->addTab('create', '__framelixdemo_view_invoices_create__', new self());
                $tabs->addTab(
                    'positions',
                    '__framelixdemo_storable_invoice_positions_label__',
                    new InvoicePositions()
                );
                $tabs->show();
        }
    }
}