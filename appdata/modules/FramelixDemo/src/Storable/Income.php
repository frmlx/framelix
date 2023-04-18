<?php

namespace Framelix\FramelixDemo\Storable;

use Framelix\FramelixDemo\Storable\SystemValue\IncomeCategory;
use Framelix\FramelixDemo\View\Incomes;
use Framelix\Framelix\Date;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Framelix\View;

/**
 * Income
 * @property Fixation|null $fixation
 * @property int|null $nr
 * @property Invoice|null $invoice
 * @property string|null $invoiceNr
 * @property Date $date
 * @property string|null $comment
 * @property IncomeCategory $incomeCategory
 * @property float $net
 */
class Income extends StorableExtended
{
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $moneyProps = ['net'];
        foreach ($moneyProps as $moneyProp) {
            $selfStorableSchema->properties[$moneyProp]->length = 14;
            $selfStorableSchema->properties[$moneyProp]->decimals = 2;
        }
        $selfStorableSchema->addIndex("date", "index");
        $selfStorableSchema->addIndex("nr", "unique");
    }

    /**
     * Get open entries (not yet fixed)
     * @return self[]
     */
    public static function getOpenEntries(): array
    {
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

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return $this->date->getHtmlString() . " | " . $this->incomeCategory->getHtmlString(
            ) . " | " . NumberUtils::format(
                $this->net,
                2
            );
    }

    /**
     * Is this storable editable
     * @return bool
     */
    public function isEditable(): bool
    {
        return !$this->fixation;
    }

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return !$this->fixation;
    }

    /**
     * Get edit url
     * @return Url|null
     */
    public function getDetailsUrl(): ?Url
    {
        return View::getUrl(Incomes::class)->setParameter('id', $this);
    }

    /**
     * @return StorableFile[]
     */
    public function getAttachments(): array
    {
        return StorableFile::getByCondition('assignedStorable = {0}', [$this]);
    }

    /**
     * Delete from database
     * @param bool $force Force deletion even if isDeletable() is false
     */
    public function delete(bool $force = false): void
    {
        self::deleteMultiple($this->getAttachments());
        parent::delete($force);
    }
}