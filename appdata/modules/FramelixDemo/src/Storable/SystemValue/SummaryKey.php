<?php

namespace Framelix\FramelixDemo\Storable\SystemValue;

use Framelix\FramelixDemo\Console;
use Framelix\FramelixDemo\Storable\Income;
use Framelix\FramelixDemo\Storable\Outgoing;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\SystemValue;
use Framelix\Framelix\Storable\User;

/**
 * Summary Key
 * @property Storable $category
 * @property string $key
 * @property string $name
 * @property int $outgoingCategory
 * @property int $incomeCategory
 */
class SummaryKey extends SystemValue
{
    public const SUM_CATEGORY_PLUS = 1;
    public const SUM_CATEGORY_MINUS = 2;

    public static function setupStorableMeta(\Framelix\Framelix\StorableMeta\SystemValue $meta): void
    {
        $meta->addDefaultPropertiesAtStart();

        $property = $meta->createProperty("key");
        $property->addDefaultField();

        $property = $meta->createProperty("name");
        $property->addDefaultField();

        $property = $meta->createProperty("outgoingCategory");
        $property->field = new Select();
        $property->field->addOption(
            SummaryKey::SUM_CATEGORY_PLUS,
            Lang::get(
                '__framelixdemo_storable_systemvalue_summarykey_summary_method_' . SummaryKey::SUM_CATEGORY_PLUS . "__"
            )
        );
        $property->field->addOption(
            SummaryKey::SUM_CATEGORY_MINUS,
            Lang::get(
                '__framelixdemo_storable_systemvalue_summarykey_summary_method_' . SummaryKey::SUM_CATEGORY_MINUS . "__"
            )
        );

        $property = $meta->createProperty("incomeCategory");
        $property->field = new Select();
        $property->field->addOption(
            SummaryKey::SUM_CATEGORY_PLUS,
            Lang::get(
                '__framelixdemo_storable_systemvalue_summarykey_summary_method_' . SummaryKey::SUM_CATEGORY_PLUS . "__"
            )
        );
        $property->field->addOption(
            SummaryKey::SUM_CATEGORY_MINUS,
            Lang::get(
                '__framelixdemo_storable_systemvalue_summarykey_summary_method_' . SummaryKey::SUM_CATEGORY_MINUS . "__"
            )
        );

        $meta->addDefaultPropertiesAtEnd();
    }

    public function isDeletable(): bool
    {
        return Console::$cleanupMode ?? false;
    }
    public function isEditable(): bool
    {
        return Console::$cleanupMode ?? User::hasRole('admin');
    }

    public function isReadable(): bool
    {
        return Console::$cleanupMode ?? User::hasRole('admin');
    }

    public function getHtmlString(): string
    {
        return $this->key . " - " . $this->name;
    }

    public function getRawTextString(): string
    {
        return $this->key;
    }

    public function getSummableNet(Income|Outgoing $storable): float
    {
        if ($storable instanceof Outgoing) {
            $net = $storable->netOperational;
            if ($this->outgoingCategory === self::SUM_CATEGORY_MINUS) {
                $net *= -1;
            }
            return $net;
        }
        $net = $storable->net;
        if ($this->incomeCategory === self::SUM_CATEGORY_MINUS) {
            $net *= -1;
        }
        return $net;
    }
}