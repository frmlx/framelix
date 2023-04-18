<?php

namespace Framelix\FramelixDemo\StorableMeta;

use Framelix\Framelix\Db\LazySearchCondition;
use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Html\QuickSearch;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\StorableMeta;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\NumberUtils;

use function is_numeric;
use function var_dump;

class Outgoing extends StorableMeta
{
    /**
     * @var \Framelix\FramelixDemo\Storable\Outgoing
     */
    public Storable $storable;

    protected function init(): void
    {
        $this->tableDefault->initialSort = ["-receiptNumber", "-date"];
        $this->tableDefault->footerSumColumns = ['net', 'netOperational'];

        $this->addDefaultPropertiesAtStart();

        $property = $this->createProperty("attachments");
        $property->field = new File();
        $property->field->multiple = true;
        $property->field->storableFileBase = new StorableFile();
        $property->field->storableFileBase->setDefaultRelativePath(false);
        $property->valueCallable = function () {
            $arr = [];
            if ($attachments = $this->storable->getAttachments()) {
                $arr = ArrayUtils::merge($arr, $attachments);
            }
            if ($attachments = $this->storable->depreciation?->getAttachments()) {
                $arr = ArrayUtils::merge($arr, $attachments);
            }
            return $arr;
        };

        $property = $this->createProperty("receiptNumber");
        $property->valueCallable = function () {
            return $this->storable->getReceiptNumber();
        };

        $property = $this->createProperty("date");
        $property->addDefaultField();

        $property = $this->createProperty("comment");
        $property->addDefaultField();

        $property = $this->createProperty("outgoingCategory");
        $property->lazySearchConditionColumns->addColumn("outgoingCategory.name", "category");
        $property->addDefaultField();

        $property = $this->createProperty("net");
        $property->addDefaultField();

        $property = $this->createProperty("netOperational");
        $property->valueCallable = function () {
            return NumberUtils::format($this->storable->netOperational, 2);
        };

        $property = $this->createProperty("operationalSharePercent");
        $property->addDefaultField();
        $property->setVisibility(self::CONTEXT_FORM, !!$this->storable->id);
        $property->valueCallable = function () {
            if ($this->context === self::CONTEXT_TABLE) {
                return $this->storable->operationalSharePercent . "%";
            }
            return $this->storable->operationalSharePercent;
        };

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