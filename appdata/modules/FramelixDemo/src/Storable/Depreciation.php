<?php

namespace Framelix\FramelixDemo\Storable;

use Framelix\FramelixDemo\Storable\SystemValue\OutgoingCategory;
use Framelix\Framelix\Date;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;

use function round;

/**
 * Depreciation
 * @property Date $date
 * @property string|null $comment
 * @property OutgoingCategory $outgoingCategory
 * @property float $netTotal
 * @property mixed $netSplit
 * @property int $years
 * @property bool $flagDone
 */
class Depreciation extends StorableExtended
{
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $moneyProps = ['netTotal'];
        foreach ($moneyProps as $moneyProp) {
            $selfStorableSchema->properties[$moneyProp]->length = 14;
            $selfStorableSchema->properties[$moneyProp]->decimals = 2;
        }
        $selfStorableSchema->properties['years']->length = 2;
        $selfStorableSchema->addIndex("date", "index");
    }

    public function getDetailsUrl(): ?Url
    {
        return View::getUrl(\Framelix\FramelixDemo\View\Depreciation::class)->setParameter('id', $this);
    }

    /**
     * Auto generate the net split for all the years
     */
    public function setNetSplit(): void
    {
        $afa = round($this->netTotal / $this->years, 2);
        $startYear = $this->date->dateTime->getYear();
        $endYear = $startYear + $this->years - 1;
        $rest = $this->netTotal;
        $isSecondHalf = $this->date->dateTime->getMonth() > 6;
        if ($isSecondHalf) {
            $endYear++;
        }
        $netSplit = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $afaUse = $afa;
            if ($year === $startYear && $isSecondHalf) {
                $afaUse = $afaUse / 2;
            }
            if ($year === $endYear) {
                $afaUse = $rest;
            }
            $afaUse = round($afaUse, 2);
            if ($afaUse > $rest) {
                $afaUse = $rest;
            }
            $netSplit[] = ['year' => $year, 'value' => $afaUse];
            $rest = round($rest - $afaUse, 2);
        }
        $this->netSplit = $netSplit;
    }

    public function isDeletable(): bool
    {
        return !$this->flagDone;
    }

    /**
     * @return StorableFile[]
     */
    public function getAttachments(): array
    {
        return StorableFile::getByCondition('assignedStorable = {0}', [$this]);
    }

    public function delete(bool $force = false): void
    {
        self::deleteMultiple($this->getAttachments());
        parent::delete($force);
    }


}