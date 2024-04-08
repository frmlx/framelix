<?php

namespace Framelix\FramelixDemo\StorableMeta;

use Framelix\Framelix\Db\LazySearchCondition;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Html\QuickSearch;
use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Html\TypeDefs\JsRenderTarget;
use Framelix\Framelix\Html\TypeDefs\JsRequestOptions;
use Framelix\Framelix\Html\TypeDefs\ModalShowOptions;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;
use Framelix\Framelix\View;
use Framelix\FramelixDemo\View\Incomes;
use Framelix\FramelixDemo\View\Invoices;

use function is_numeric;

class Invoice extends StorableMeta
{

    /**
     * @var \Framelix\FramelixDemo\Storable\Invoice
     */
    public Storable $storable;

    protected function init(): void
    {
        $isOffer = $this->parameters['category'] === \Framelix\FramelixDemo\Storable\Invoice::CATEGORY_OFFER;

        $this->tableDefault->initialSort = ["-invoiceNr"];
        $this->tableDefault->footerSumColumns = ['net'];

        if ($isOffer) {
            $property = $this->createProperty("copyInvoiceToOffer");
            $property->setVisibility(null, false);
            $property->setVisibility(self::CONTEXT_TABLE, true);
            $property->setLabel('');
            $property->valueCallable = function () {
                return TableCell::create('<framelix-button icon="78a" theme="primary" href="' . View::getUrl(Invoices::class)->setParameter(
                        'category',
                        \Framelix\FramelixDemo\Storable\Invoice::CATEGORY_INVOICE
                    )->setParameter('copy',
                        $this->storable) . '" title="__framelixdemo_storable_invoice_offfertoinvoice__"></framelix-button>');
            };
        }

        $this->tableDefault->addColumnFlag('copyInvoice', Table::COLUMNFLAG_REMOVE_IF_EMPTY);
        $property = $this->createProperty("copyInvoice");
        $property->setVisibility(null, false);
        $property->setVisibility(self::CONTEXT_TABLE, true);
        $property->setLabel('');
        $property->valueCallable = function () {
            return TableCell::create('<framelix-button icon="78a" theme="success" href="' . View::getUrl(Invoices::class)->setParameter(
                    'category',
                    $this->storable->category
                )->setParameter('copy',
                    $this->storable) . '" title="__framelixdemo_storable_invoice_copy__"></framelix-button>');
        };

        $this->tableDefault->addColumnFlag('downloadInvoice', Table::COLUMNFLAG_REMOVE_IF_EMPTY);
        $property = $this->createProperty("downloadInvoice");
        $property->setVisibility(null, false);
        $property->setVisibility(self::CONTEXT_TABLE, true);
        $property->setLabel('');
        $property->valueCallable = function () {
            $requestOptions = new JsRequestOptions(
                JsCall::getUrl(
                    Invoices::class,
                    'invoice-pdf-download',
                    ['invoice' => $this->storable]
                ), new JsRenderTarget(modalOptions: new ModalShowOptions(maxWidth: 500))
            );
            return TableCell::create('<framelix-button icon="73e" theme="error" title="__framelixdemo_storable_invoice_download__" request-options=\'' . $requestOptions . '\'></framelix-button>');
        };

        if (!$isOffer) {
            $this->tableDefault->addColumnFlag('createIncome', Table::COLUMNFLAG_REMOVE_IF_EMPTY);
            $property = $this->createProperty("createIncome");
            $property->setVisibility(null, false);
            $property->setVisibility(self::CONTEXT_TABLE, true);
            $property->setLabel('');
            $property->valueCallable = function () {
                if (!$this->storable->income && $this->storable->datePaid) {
                    return TableCell::create('<framelix-button icon="78c" theme="primary" href="' . View::getUrl(Incomes::class)->setParameter('fromInvoice',
                            $this->storable) . '" title="__framelixdemo_storable_invoice_createincome__"></framelix-button>');
                }
                return null;
            };
        }

        $this->addDefaultPropertiesAtStart();

        $this->tableDefault->addColumnFlag('invoiceNr', Table::COLUMNFLAG_SMALLWIDTH);
        $property = $this->createProperty("invoiceNr");

        $property = $this->createProperty("date");
        $property->addDefaultField();

        if (!$isOffer) {
            $property = $this->createProperty("datePaid");
            $property->addDefaultField();

            $property = $this->createProperty("performancePeriod");
            $property->addDefaultField();
            $property->setVisibility(self::CONTEXT_TABLE, false);

            $property = $this->createProperty("incomeCategory");
            $property->lazySearchConditionColumns->addColumn("incomeCategory.name", "category");
            $property->addDefaultField();
            $property->field->required = true;
        }

        $property = $this->createProperty("creator");
        $property->lazySearchConditionColumns->addColumn("creator.address", "creator");
        $property->addDefaultField();
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $property = $this->createProperty("receiverVatId");
        $property->addDefaultField();
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $property = $this->createProperty("receiver");
        $property->field = new Textarea();

        $property = $this->createProperty("textBeforePosition");
        $property->addDefaultField();
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $property = $this->createProperty("textAfterPosition");
        $property->addDefaultField();
        $property->setVisibility(self::CONTEXT_TABLE, false);

        $property = $this->createProperty("flagReverseCharge");
        $property->addDefaultField();
        $property->setVisibility(self::CONTEXT_TABLE, false);

        if ($this->storable->income) {
            $property = $this->createProperty("income");
            $property->field = new Html();
            $property->setVisibility(self::CONTEXT_TABLE, false);
            $property->valueCallable = function () {
                if ($this->storable->income && $this->context === self::CONTEXT_FORM) {
                    return '<a href="' . $this->storable->income->getDetailsUrl() . '" target="_blank">' . $this->storable->income->getHtmlString() . '</a>';
                }
                return $this->storable->income;
            };
        }

        if (!$isOffer && $this->storable->attachment) {
            $property = $this->createProperty("deleteInvoice");
            $property->field = new Toggle();
            $property->setVisibility(null, false);
            $property->setVisibility(self::CONTEXT_FORM, true);
        }

        $this->createProperty("net");

        $this->addDefaultPropertiesAtEnd();
    }

    public function getQuickSearch(): QuickSearch
    {
        $quickSearch = parent::getQuickSearch();
        $field = new Select();
        $fixations = \Framelix\FramelixDemo\Storable\Fixation::getByCondition(sort: '-dateFrom');
        $field->name = "fixation";
        $field->chooseOptionLabel = '__framelixdemo_search_option_choose_fixation__';
        $field->addOption("nofixation", '__framelixdemo_search_option_nofixation__');
        foreach ($fixations as $fixation) {
            $field->addOption(
                $fixation,
                $fixation->dateFrom->getHtmlString() . " - " . $fixation->dateTo->getHtmlString()
            );
        }
        $quickSearch->addOptionField($field);
        return $quickSearch;
    }

    public function getQuickSearchCondition(array $options = null): LazySearchCondition
    {
        $condition = parent::getQuickSearchCondition($options);
        if ($options['fixation'] ?? false) {
            if ($options['fixation'] === 'nofixation') {
                $condition->prependFixedCondition = "fixation IS NULL";
            } elseif (is_numeric($options['fixation'])) {
                $condition->prependFixedCondition = "fixation = " . (int)$options['fixation'];
            }
        }
        return $condition;
    }

}