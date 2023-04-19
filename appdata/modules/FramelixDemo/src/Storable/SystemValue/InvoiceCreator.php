<?php

namespace Framelix\FramelixDemo\Storable\SystemValue;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Form\Field\Bic;
use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Form\Field\Iban;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Storable\SystemValue;
use Framelix\Framelix\Storable\User;
use Framelix\FramelixDemo\Console;

/**
 * Invoice Creator
 * @property StorableFile|null $invoiceHeader
 * @property StorableFile|null $invoiceFooter
 * @property string|null $vatId
 * @property string $address
 * @property string $invoiceTextAfterPositions
 * @property string|null $accountName
 * @property string|null $iban
 * @property string|null $bic
 */
class InvoiceCreator extends SystemValue
{
    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['address']->databaseType = 'text';
        $selfStorableSchema->properties['address']->length = null;
        $selfStorableSchema->properties['invoiceTextAfterPositions']->databaseType = 'text';
        $selfStorableSchema->properties['invoiceTextAfterPositions']->length = null;
    }

    public static function setupStorableMeta(\Framelix\Framelix\StorableMeta\SystemValue $meta): void
    {
        $meta->addDefaultPropertiesAtStart();

        $property = $meta->createProperty("invoiceHeader");
        $property->field = new File();
        $property->field->storableFileBase = new StorableFile();
        $property->field->storableFileBase->setDefaultRelativePath(false);
        $property->field->allowedFileTypes = ".jpg, .png";

        $property = $meta->createProperty("invoiceFooter");
        $property->field = new File();
        $property->field->storableFileBase = new StorableFile();
        $property->field->storableFileBase->setDefaultRelativePath(false);
        $property->field->allowedFileTypes = ".jpg, .png";

        $property = $meta->createProperty("vatId");
        $property->addDefaultField();

        $property = $meta->createProperty("address");
        $property->field = new Textarea();

        $property = $meta->createProperty("invoiceTextAfterPositions");
        $property->field = new Textarea();
        $property->setVisibility($meta::CONTEXT_TABLE, false);

        $property = $meta->createProperty("accountName");
        $property->addDefaultField();
        $property->setVisibility($meta::CONTEXT_TABLE, false);

        $property = $meta->createProperty("iban");
        $property->field = new Iban();
        $property->setVisibility($meta::CONTEXT_TABLE, false);

        $property = $meta->createProperty("bic");
        $property->field = new Bic();
        $property->setVisibility($meta::CONTEXT_TABLE, false);

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
        return $this->address;
    }
}