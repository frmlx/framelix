<?php

namespace Framelix\FramelixDemo\StorableMeta;

use Framelix\Framelix\Date;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

use function array_reverse;

class Fixation extends StorableMeta
{
    /**
     * @var \Framelix\FramelixDemo\Storable\Fixation
     */
    public Storable $storable;

    protected function init(): void
    {
        $this->tableDefault->initialSort = ["-dateFrom"];

        $property = $this->createProperty("pdf");
        $property->setVisibility(null, false);
        $property->setVisibility(self::CONTEXT_TABLE, true);
        $property->setLabel("");
        $property->valueCallable = function () {
            $downloadUrl = $this->storable->attachment?->getDownloadUrl();
            if (!$downloadUrl) {
                return null;
            }
            return TableCell::create('<framelix-button icon="709" href="' . $downloadUrl . '" title="__framelix_download_file__"></framelix-button>');
        };

        $this->addDefaultPropertiesAtStart();


        $minMax = \Framelix\FramelixDemo\Storable\Fixation::getNextFixationDateRange();
        $range = Date::rangeDays($minMax[0], $minMax[1]);
        $property = $this->createProperty("dateFrom");
        $property->field = new Select();
        $property->field->searchable = true;
        foreach ($range as $date) {
            $property->field->addOption($date->getDbValue(), $date);
        }

        $property = $this->createProperty("dateTo");
        $property->field = new Select();
        $property->field->searchable = true;
        $range = array_reverse($range);
        foreach ($range as $date) {
            $property->field->addOption($date->getDbValue(), $date);
        }

        $this->addDefaultPropertiesAtEnd();
    }
}