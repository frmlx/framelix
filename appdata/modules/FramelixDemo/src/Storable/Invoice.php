<?php

namespace Framelix\FramelixDemo\Storable;

use Framelix\FramelixDemo\Storable\SystemValue\IncomeCategory;
use Framelix\FramelixDemo\Storable\SystemValue\InvoiceCreator;
use Framelix\FramelixDemo\View\Invoices;
use Framelix\Framelix\Date;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableArray;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Framelix\View;

use function sprintf;

/**
 * Invoice
 * @property StorableFile|null $attachment
 * @property Fixation|null $fixation
 * @property Income|null $income
 * @property int $category
 * @property int|null $invoiceNr
 * @property Date $date
 * @property Date|null $datePaid
 * @property string|null $performancePeriod
 * @property float $net
 * @property IncomeCategory|null $incomeCategory
 * @property InvoiceCreator $creator
 * @property string|null $receiverVatId
 * @property string $receiver
 * @property string|null $textBeforePosition
 * @property string|null $textAfterPosition
 * @property mixed|null $bankData
 * @property bool|null $flagReverseCharge
 */
class Invoice extends StorableExtended
{
    public const CATEGORY_INVOICE = 1;
    public const CATEGORY_OFFER = 2;

    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $moneyProps = ['net'];
        $selfStorableSchema->properties["category"]->length = 2;
        foreach ($moneyProps as $moneyProp) {
            $selfStorableSchema->properties[$moneyProp]->length = 14;
            $selfStorableSchema->properties[$moneyProp]->decimals = 2;
        }
        $textProps = ['textBeforePosition', 'textAfterPosition'];
        foreach ($textProps as $textProp) {
            $selfStorableSchema->properties[$textProp]->databaseType = 'text';
            $selfStorableSchema->properties[$textProp]->length = null;
        }
        $selfStorableSchema->addIndex("date", "index");
        $selfStorableSchema->addIndex("invoiceNr", "unique");
    }

    public function getDetailsUrl(): ?Url
    {
        return View::getUrl(Invoices::class)->setParameter('category', $this->category)->setParameter('id', $this);
    }

    /**
     * Get open entries (not yet fixed)
     * @param int $category
     * @return self[]
     */
    public static function getOpenEntries(int $category): array
    {
        return self::getByCondition('fixation IS NULL && category = {0}', [$category], '+invoiceNr');
    }

    public function getPositions(): array
    {
        return InvoicePosition::getByCondition('invoice = {0}', [(int)$this->id], ['+sort']);
    }

    public function isEditable(): bool
    {
        return !$this->getOriginalDbValueForProperty("income") && !$this->fixation;
    }

    public function isDeletable(): bool
    {
        return !$this->income && !$this->fixation;
    }

    public function getRawTextString(): string
    {
        return $this->date->getHtmlString() . " | " . NumberUtils::format($this->net, 2);
    }

    public function getHtmlString(): string
    {
        return $this->date->getHtmlString() . " | " . NumberUtils::format($this->net, 2);
    }

    /**
     * @return StorableFile[]
     */
    public function getAttachments(): array
    {
        return StorableFile::getByCondition('assignedStorable = {0}', [$this]);
    }

    /**
     * @return StorableFile[]
     */
    public function getInvoiceCopies(): array
    {
        if (!$this->id) {
            return [];
        }
        return StorableArray::getValues($this);
    }

    public function store(bool $force = false): void
    {
        if (!$this->id) {
            $this->net = 0.0;
            $nr = 1;
            while (true) {
                $invoiceNr = (int)($this->date->dateTime->format("ymd") . sprintf("%02d", $nr));
                if (!self::getByConditionOne('invoiceNr = {0}', [$invoiceNr])) {
                    $this->invoiceNr = $invoiceNr;
                    break;
                }
                $nr++;
            }
        }
        parent::store($force);
    }

    public function delete(bool $force = false): void
    {
        self::deleteMultiple($this->getAttachments());
        self::deleteMultiple($this->getInvoiceCopies());
        parent::delete($force);
    }
}