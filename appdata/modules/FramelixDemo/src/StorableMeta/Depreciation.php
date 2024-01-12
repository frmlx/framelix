<?php

namespace Framelix\FramelixDemo\StorableMeta;

use Framelix\Framelix\Db\LazySearchCondition;
use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Html\QuickSearch;
use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\StorableMeta;
use Framelix\Framelix\View;
use Framelix\FramelixDemo\View\Outgoings;

use function date;

class Depreciation extends StorableMeta
{
    /**
     * @var \Framelix\FramelixDemo\Storable\Depreciation
     */
    public Storable $storable;

    protected function init(): void
    {
        $this->tableDefault->addColumnFlag('createIncome', Table::COLUMNFLAG_REMOVE_IF_EMPTY);

        $property = $this->createProperty("createIncome");
        $property->setVisibility(null, false);
        $property->setVisibility(self::CONTEXT_TABLE, true);
        $property->setLabel('');
        $property->valueCallable = function () {
            $year = (int)date("Y");
            $yearSplitRequired = false;
            if ($this->storable->netSplit) {
                foreach ($this->storable->netSplit as $row) {
                    if ($row['year'] === $year && !\Framelix\FramelixDemo\Storable\Outgoing::getByCondition(
                            'depreciation = {0} && strftime(\'%Y\', date) = {1}',
                            [$this->storable->id, $year]
                        )) {
                        $yearSplitRequired = true;
                        break;
                    }
                }
            }
            if ($yearSplitRequired) {
                $tableCell = new TableCell();
                $tableCell->button = true;
                $tableCell->buttonIcon = "705";
                $tableCell->buttonTooltip = "__framelixdemo_storable_depreciation_createoutgoing__";
                $tableCell->buttonTheme = "primary";
                $tableCell->buttonHref = View::getUrl(Outgoings::class)->setParameter(
                    'fromDepreciation',
                    $this->storable
                );
                $tableCell->buttonTarget = "_blank";
                return $tableCell;
            }
            return null;
        };


        $this->addDefaultPropertiesAtStart();

        $property = $this->createProperty("attachments");
        $property->field = new File();
        $property->field->multiple = true;
        $property->field->storableFileBase = new StorableFile();

        $property = $this->createProperty("date");
        $property->addDefaultField();

        $property = $this->createProperty("comment");
        $property->addDefaultField();

        $property = $this->createProperty("outgoingCategory");
        $property->addDefaultField();

        $property = $this->createProperty("netTotal");
        $property->addDefaultField();

        $property = $this->createProperty("years");
        $property->addDefaultField();

        $property = $this->createProperty("flagDone");
        $property->addDefaultField();

        $this->addDefaultPropertiesAtEnd();
    }

    public function getQuickSearch(): QuickSearch
    {
        $quickSearch = parent::getQuickSearch();
        $quickSearch->addOptionToggle("noclosed", "__framelixdemo_search_option_noclosed__", true);
        return $quickSearch;
    }

    public function getQuickSearchCondition(array $options = null): LazySearchCondition
    {
        $condition = parent::getQuickSearchCondition($options);
        if ($options['noclosed'] ?? false) {
            $condition->prependFixedCondition = "flagDone = 0";
        }
        return $condition;
    }
}