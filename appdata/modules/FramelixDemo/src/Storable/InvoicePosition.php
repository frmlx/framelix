<?php

namespace Framelix\FramelixDemo\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Url;
use Framelix\FramelixDemo\Console;

/**
 * @property Invoice $invoice
 * @property int $count
 * @property float $netSingle
 * @property string|null $comment
 * @property int $sort
 */
class InvoicePosition extends StorableExtended
{
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $moneyProps = ['netSingle'];
        foreach ($moneyProps as $moneyProp) {
            $selfStorableSchema->properties[$moneyProp]->length = 14;
            $selfStorableSchema->properties[$moneyProp]->decimals = 2;
            $selfStorableSchema->properties['comment']->databaseType = 'text';
            $selfStorableSchema->properties['comment']->length = null;
        }
    }

    public function isDeletable(): bool
    {
        return Console::$cleanupMode ?? $this->invoice?->isDeletable() ?? true;
    }

    protected function onDatabaseUpdated(): void
    {
        $this->updateInvoiceNet();
    }

    private function updateInvoiceNet(): void
    {
        $invoice = $this->invoice;
        if (!$invoice) {
            return;
        }
        $invoice->net = (float)$this->getDb()->fetchOne(
            "
            SELECT ROUND(SUM(count * netSingle), 2) 
            FROM `" . __CLASS__ . "`
            WHERE invoice = " . $invoice->id . "          
        "
        );
        $invoice->store();
    }

    public function getDetailsUrl(): ?Url
    {
        return $this->invoice->getDetailsUrl()->setParameter('idPosition', $this);
    }


}