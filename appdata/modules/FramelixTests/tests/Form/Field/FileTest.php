<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\Storable\TestStorableFile;
use Framelix\FramelixTests\TestCase;

use function reset;

final class FileTest extends TestCase
{
    public function tests(): void
    {
        $this->setupDatabase();
        $fieldBase = new File();
        $fieldBase->name = $fieldBase::class;
        $fieldBase->required = true;
        $this->callFormFieldDefaultMethods($fieldBase);

        $this->setSimulatedPostData([$fieldBase->name => "#aaaaaa"]);
        $this->assertSame(Lang::get('__framelix_form_validation_required__'), $fieldBase->validate());

        // update name to prevent caching of converted submitted values
        $field = clone $fieldBase;
        $this->addSimulatedFile($field->name, 'test', false);
        $this->assertInstanceOf(UploadedFile::class, $field->getConvertedSubmittedValue()[0]);
        $this->assertTrue($field->validate());
        $this->removeSimulatedFile($field->name);

        // validators
        $field = clone $fieldBase;
        $field->minSelectedFiles = 2;
        $this->addSimulatedFile($field->name, 'test', false);
        $this->assertIsString($field->validate());
        $this->removeSimulatedFile($field->name);

        $field = clone $fieldBase;
        $field->maxSelectedFiles = 1;
        $field->minSelectedFiles = null;
        $this->addSimulatedFile($field->name, 'test', true);
        $this->assertIsString($field->validate());
        $this->removeSimulatedFile($field->name);

        $field = clone $fieldBase;
        $field->defaultValue = [new TestStorableFile()];
        $this->assertInstanceOf(PhpToJsData::class, $field->jsonSerialize());

        // test store
        $storable = TestStorable2::getNewTestInstance();
        $storable->store();
        $field = clone $fieldBase;
        $field->name = "storableFileOptional";
        $this->addSimulatedFile($field->name, 'test', false);
        $field->storableFileBase = new TestStorableFile();
        $field->storableFileBase->setDefaultRelativePath(false);
        $field->setOnlyImages();
        $field->setOnlyVideos();
        $files = $field->store($storable);
        $this->removeSimulatedFile($field->name);
        $createdFile = reset($files['created']);
        $this->assertInstanceOf(TestStorableFile::class, $createdFile);
        $this->assertSame($storable, $createdFile->assignedStorable);
        $this->assertSame($createdFile, $storable->storableFileOptional);

        // test default values
        $field->defaultValue = $createdFile;
        $this->assertInstanceOf(PhpToJsData::class, $field->jsonSerialize());

        // test delete
        $this->setSimulatedPostData([$field->name => [$createdFile->id => "0"]]);
        $files = $field->store($storable);
        $this->assertSame(1, $files['deleted']);
        $this->assertSame(null, $createdFile->id);
        $this->assertSame(null, $storable->storableFileOptional);

        // test store multiple
        $storable = TestStorable2::getNewTestInstance();
        $storable->store();
        $field = clone $fieldBase;
        $field->multiple = true;
        $field->name = "storableFileArrayOptional";
        $this->addSimulatedFile($field->name, 'test', true);
        $field->storableFileBase = new TestStorableFile();
        $field->storableFileBase->setDefaultRelativePath(false);
        $field->setOnlyImages();
        $field->setOnlyVideos();
        $files = $field->store($storable);
        $this->removeSimulatedFile($field->name);
        $createdFile = reset($files['created']);
        $this->assertInstanceOf(TestStorableFile::class, $createdFile);
        $this->assertSame($storable, $createdFile->assignedStorable);
        $this->assertSame($files['created'], $storable->storableFileArrayOptional);
        $this->assertCount(2, $files['created']);

        // test delete multiple
        $arr = [];
        foreach ($files['created'] as $file) {
            $arr[$field->name][$file->id] = "0";
        }
        $this->setSimulatedPostData($arr);
        $files = $field->store($storable);
        $this->assertSame(2, $files['deleted']);
        $this->assertSame(null, $createdFile->id);
        $this->assertSame(null, $storable->storableFileArrayOptional);
    }
}
