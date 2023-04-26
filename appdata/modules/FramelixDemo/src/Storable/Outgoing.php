<?php

namespace Framelix\FramelixDemo\Storable;

use Framelix\Framelix\Date;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Framelix\View;
use Framelix\FramelixDemo\Storable\SystemValue\OutgoingCategory;
use Framelix\FramelixDemo\View\Outgoings;

use function round;

/**
 * @property Depreciation|null $depreciation
 * @property Fixation|null $fixation
 * @property int|null $nr
 * @property Date $date
 * @property string|null $comment
 * @property OutgoingCategory $outgoingCategory
 * @property float $net
 * @property float $netOperational
 * @property int $operationalSharePercent
 */
class Outgoing extends StorableExtended
{
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $moneyProps = ['net', 'netOperational'];
        foreach ($moneyProps as $moneyProp) {
            $selfStorableSchema->properties[$moneyProp]->length = 14;
            $selfStorableSchema->properties[$moneyProp]->decimals = 2;
        }
        $selfStorableSchema->properties['operationalSharePercent']->length = 3;
        $selfStorableSchema->addIndex("date", "index");
        $selfStorableSchema->addIndex("nr", "unique");
    }

    /**
     * Get open entries (not yet fixed)
     * @return self[]
     */
    public static function getOpenEntries(): array
    {
        Mysql::$logExecutedQueries = true;
        return self::getByCondition('fixation IS NULL', sort: ['+date', '+id']);
    }

    /**
     * Get receipt number
     * @return int|null
     */
    public function getReceiptNumber(): ?int
    {
        if (!$this->nr) {
            return null;
        }
        return $this->nr;
    }

    public function getHtmlString(): string
    {
        return $this->date->getHtmlString() . " | " . $this->outgoingCategory->getHtmlString(
            ) . " | " . NumberUtils::format(
                $this->net,
                2
            );
    }

    public function getDetailsUrl(): ?Url
    {
        return View::getUrl(Outgoings::class)->setParameter('id', $this);
    }

    public function isEditable(): bool
    {
        return !$this->fixation;
    }

    public function isDeletable(): bool
    {
        return !$this->fixation;
    }

    /**
     * @return StorableFile[]
     */
    public function getAttachments(): array
    {
        return StorableFile::getByCondition('assignedStorable = {0}', [$this]);
    }

    public function store(bool $force = false): void
    {
        if ($this->operationalSharePercent === null || $this->getOriginalDbValueForProperty(
                'operationalSharePercent'
            ) !== $this->getNewDbValueForProperty('operationalSharePercent')) {
            $this->operationalSharePercent = $this->outgoingCategory->operationalSharePercent ?? 100;
            $percent = $this->operationalSharePercent;
            if ($percent > 100) {
                $percent = 100;
            } elseif ($percent < 0) {
                $percent = 0;
            }
            $this->netOperational = round($this->net / 100 * $percent, 2);
        }
        parent::store($force);
    }

    public function delete(bool $force = false): void
    {
        self::deleteMultiple($this->getAttachments());
        parent::delete($force);
    }
}