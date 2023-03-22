<?php

namespace Form\Field;

use Framelix\Framelix\Db\StorableSchemaProperty;
use Framelix\Framelix\Form\Field\MediaBrowser;
use Framelix\Framelix\Form\Field\MediaBrowserSelection;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Storable\StorableFolder;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\FramelixTests\Storable\TestStorableFile;
use Framelix\FramelixTests\TestCase;

use function json_encode;

final class MediaBrowserTest extends TestCase
{
    public function tests(): void
    {
        $this->setupDatabase();

        $baseBrowser = new MediaBrowser();
        $baseBrowser->name = $baseBrowser::class;
        $baseBrowser->fileClass = TestStorableFile::class;
        $baseBrowser->setDefaultRelativePath(false);

        $folder = new StorableFolder();
        $folder->name = "root";
        $folder->store();

        $folder2 = new StorableFolder();
        $folder2->name = "sub";
        $folder2->parent = $folder;
        $folder2->store();

        $folder3 = new StorableFolder();
        $folder3->name = "subber";
        $folder3->parent = $folder2;
        $folder3->store();

        // generic calls
        $field = clone $baseBrowser;
        $field->setOnlyVideos();
        $field->setOnlyImages();
        $field->setOnlyThumbnailableImages();
        $this->assertInstanceOf(PhpToJsData::class, $field->jsonSerialize());
        $this->assertNull($field->getConvertedSubmittedValue());

        // simulate uploading a file
        $field = clone $baseBrowser;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $this->addSimulatedFile('file', 'test.jpg', false, 'test.jpg');
        $jsCall = new JsCall('', null);
        MediaBrowser::onJsCall($jsCall);
        $createdFileJpg = TestStorableFile::getById($jsCall->result['fileId']);
        $this->assertSame('success', $jsCall->result['type']);

        // simulate uploading a file in a folder
        $field = clone $baseBrowser;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $this->addSimulatedFile('file', 'test.mp4', false, 'test.mp4');
        $jsCall = new JsCall('', ["currentFolder" => $folder->id]);
        MediaBrowser::onJsCall($jsCall);
        $createdFileMp4 = TestStorableFile::getById($jsCall->result['fileId']);
        $this->assertSame('success', $jsCall->result['type']);

        // simulate uploading a file in a folder with root folder restriction
        $field = clone $baseBrowser;
        $field->rootFolder = $folder;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $this->addSimulatedFile('file', 'test.svg', false, 'test.svg');
        $this->setSimulatedPostData(['parameters' => JsonUtils::encode(["currentFolder" => $folder2->id])]);
        $jsCall = new JsCall('', null);
        MediaBrowser::onJsCall($jsCall);
        $createdFileSvg = TestStorableFile::getById($jsCall->result['fileId']);
        $this->assertSame('success', $jsCall->result['type']);

        // simulate uploading a file in a folder with root folder restriction violation
        // effectively set folder to root folder
        $field = clone $baseBrowser;
        $field->rootFolder = $folder2;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $this->addSimulatedFile('file', 'test', false);
        $this->setSimulatedPostData(['parameters' => JsonUtils::encode(["currentFolder" => $folder->id])]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertSame('success', $jsCall->result['type']);
        $createdFileTxt = TestStorableFile::getById($jsCall->result['fileId']);
        $this->assertSame($folder2, $createdFileTxt->storableFolder);

        // simulate replacing a file
        $field = clone $baseBrowser;
        $field->rootFolder = $folder2;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $this->addSimulatedFile('file', 'test', false);
        $this->setSimulatedPostData(
            ['parameters' => JsonUtils::encode(["currentFolder" => $folder->id, "replaceId" => $createdFileTxt->id])]
        );
        MediaBrowser::onJsCall($jsCall);
        $this->assertSame('success', $jsCall->result['type']);
        $this->assertSame(TestStorableFile::getById($jsCall->result['fileId']), $createdFileTxt);

        // simulate uploading an unsupported extension
        $field = clone $baseBrowser;
        $field->setOnlyImages();
        $this->setSimulatedUrl($field->getJsCallUrl());
        $this->addSimulatedFile('file', 'test', false);
        $jsCall = new JsCall('', null);
        MediaBrowser::onJsCall($jsCall);
        $this->assertSame('error', $jsCall->result['type']);

        $this->removeSimulatedFile('file');

        // metadata
        $field = clone $baseBrowser;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $jsCall = new JsCall('metadata', ['id' => $createdFileJpg->id]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertInstanceOf(Url::class, $jsCall->result['url'] ?? null);

        // metadata with root folder restriction
        $field = clone $baseBrowser;
        $field->rootFolder = $folder2;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $jsCall = new JsCall('metadata', ['id' => $createdFileTxt->id]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertInstanceOf(Url::class, $jsCall->result['url'] ?? null);

        // metadata with root folder restriction violation
        $field = clone $baseBrowser;
        $field->rootFolder = $folder3;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $jsCall = new JsCall('metadata', ['id' => $createdFileTxt->id]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertNull($jsCall->result);

        $selection = new MediaBrowserSelection();
        $selection::setupSelfStorableSchemaProperty(new StorableSchemaProperty());
        $selection->selection = [$createdFileTxt->id, $createdFileSvg->id, $folder2->id];
        $selection->sortedFiles = [$createdFileSvg->id, $createdFileTxt->id];
        $this->assertInstanceOf(
            MediaBrowserSelection::class,
            $selection::createFromDbValue(JsonUtils::encode(JsonUtils::decode($selection)))
        );
        $this->assertNull($selection::create(1));
        $this->assertInstanceOf(MediaBrowserSelection::class, $selection::create($selection));
        $this->assertInstanceOf(
            MediaBrowserSelection::class,
            $selection::createFromFormValue(['selection' => $selection->selection])
        );
        $this->assertInstanceOf(
            TestStorableFile::class,
            $selection->getSelectionFirstFile()
        );
        $this->assertIsArray($selection->getSelectionFoldersAndFiles());
        $this->assertIsString($selection->getHtmlString());
        $this->assertIsString($selection->getHtmlTableValue());
        $this->assertIsString($selection->getRawTextString());
        $this->assertIsInt($selection->getSortableValue());

        $field = clone $baseBrowser;
        $this->setSimulatedUrl($field->getJsCallUrl());
        Buffer::start();
        $jsCall = new JsCall('edit-selection-info', ['value' => json_encode($selection)]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertMatchesRegularExpression("~mediabrowser~", Buffer::get());

        $field = clone $baseBrowser;
        $this->setSimulatedUrl($field->getJsCallUrl());
        $jsCall = new JsCall('selectioninfo', ['value' => json_encode($selection), 'selectionInfoMaxThumbs' => 1]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertMatchesRegularExpression("~mediabrowser~", $jsCall->result);

        $field = clone $baseBrowser;
        $this->setSimulatedUrl($field->getJsCallUrl());
        Buffer::start();
        $jsCall = new JsCall('edit-file-list', ['id' => $createdFileJpg->id]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertMatchesRegularExpression("~mediabrowser~", Buffer::get());

        $field = clone $baseBrowser;
        $field->multiple = true;
        $this->setSimulatedUrl($field->getJsCallUrl());
        Buffer::start();
        $jsCall = new JsCall('browser', []);
        MediaBrowser::onJsCall($jsCall);
        $this->assertMatchesRegularExpression("~mediabrowser~", Buffer::get());

        $this->setSimulatedUrl(
            JsCall::getUrl(
                MediaBrowser::class,
                'edit-file-save',
                ['id' => $createdFileJpg->id, 'fileClass' => TestStorableFile::class]
            )
        );
        $jsCall = new JsCall('edit-file-save', ['value' => "blub"]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertSame("blub", $createdFileJpg->filename);

        Buffer::start();
        $this->setSimulatedUrl(
            JsCall::getUrl(
                MediaBrowser::class,
                'edit-folder-list',
                ['fileClass' => TestStorableFile::class]
            )
        );
        $jsCall = new JsCall('edit-folder-list', ['id' => $folder->id]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertMatchesRegularExpression("~mediabrowser~", Buffer::get());

        $this->setSimulatedUrl(
            JsCall::getUrl(
                MediaBrowser::class,
                'createfolder',
                ['fileClass' => TestStorableFile::class]
            )
        );
        $jsCall = new JsCall('createfolder', ['value' => "blub"]);
        MediaBrowser::onJsCall($jsCall);
        $createdFolder = StorableFolder::getById($jsCall->result['folderId']);

        $this->setSimulatedUrl(
            JsCall::getUrl(
                MediaBrowser::class,
                'edit-folder-save',
                ['id' => $createdFolder->id, 'fileClass' => TestStorableFile::class]
            )
        );
        $jsCall = new JsCall('edit-folder-save', ['value' => "blubabla"]);
        MediaBrowser::onJsCall($jsCall);
        $this->assertSame('blubabla', $createdFolder->name);

        $this->setSimulatedUrl(
            JsCall::getUrl(
                MediaBrowser::class,
                'delete-folder',
                ['id' => $createdFolder->id, 'fileClass' => TestStorableFile::class]
            )
        );
        $jsCall = new JsCall('delete-folder', []);
        MediaBrowser::onJsCall($jsCall);
        $this->assertNull($createdFolder->id);

        $this->setSimulatedUrl(
            JsCall::getUrl(
                MediaBrowser::class,
                'delete-file',
                ['id' => $createdFileTxt->id, 'fileClass' => TestStorableFile::class]
            )
        );
        $jsCall = new JsCall('delete-file', []);
        MediaBrowser::onJsCall($jsCall);
        $this->assertNull($createdFileTxt->id);
    }
}
