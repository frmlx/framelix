<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Storable\StorableFolder;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Throwable;

use function implode;
use function in_array;
use function is_array;

use const FRAMELIX_MODULE;

class MediaBrowser extends Field
{
    /**
     * The base file class to use
     * @var string
     */
    public string $fileClass = StorableFile::class;

    /**
     * The relative path on disk starting from /framelix/userdata to store files in
     * @var string
     */
    public string $relativePathOnDisk;

    /**
     * Allowed file extensions
     * @var string[]|null
     */
    public ?array $allowedExtensions = null;

    /**
     * The base folder to start from
     * Use cannot break out of this base folder
     * @var StorableFolder|null
     */
    public StorableFolder|null $rootFolder = null;

    /**
     * Allow multiple select
     * @var bool
     */
    public bool $multiple = false;

    /**
     * Show max number of thumbnails for the user selected files
     * If more files are selected, it shows ... after the number of thumbs
     * @var int
     */
    public int $selectionInfoMaxThumbs = 5;

    public static function onJsCall(JsCall $jsCall): void
    {
        $allowedExtensions = Request::getGet('allowedExtensions');
        $relativePathOnDisk = Request::getGet('relativePathOnDisk');
        $disabled = !!Request::getGet('disabled');
        $rootFolder = StorableFolder::getById(Request::getGet('rootFolder'));
        $fileClass = Request::getGet('fileClass');
        $emptyNewFile = null;
        if ($fileClass) {
            /** @var StorableFile $fileClass */
            $emptyNewFile = new $fileClass();
        }
        $multiple = !!Request::getGet('multiple');
        $currentFolder = StorableFolder::getById($jsCall->parameters['currentFolder'] ?? null) ?? $rootFolder;
        $restrictCurrentFolder = function (?StorableFolder $currentFolder, ?StorableFolder $rootFolder) {
            if ($rootFolder && !$currentFolder) {
                return $rootFolder;
            }
            if ($rootFolder) {
                $parent = $currentFolder;
                $validInFolder = false;
                while ($parent) {
                    if ($parent === $rootFolder) {
                        $validInFolder = true;
                        break;
                    }
                    $parent = $parent->parent;
                }
                if (!$validInFolder) {
                    return $rootFolder;
                }
            }
            return $currentFolder;
        };
        $currentFolder = $restrictCurrentFolder($currentFolder, $rootFolder);
        if (isset($_FILES['file'])) {
            $parameters = Request::getPost('parameters') ? JsonUtils::decode(Request::getPost('parameters')) : null;
            $uploadedFiles = UploadedFile::createFromSubmitData('file');
            $currentFolder = StorableFolder::getById($parameters['currentFolder'] ?? 0);
            $currentFolder = $restrictCurrentFolder($currentFolder, $rootFolder);
            try {
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($allowedExtensions && !in_array($uploadedFile->getExtension(), $allowedExtensions)) {
                        $jsCall->result = [
                            'type' => 'error',
                            'message' => Lang::get(
                                '__framelix_mediabrowser_upload_extension_blocked__',
                                [$uploadedFile->name, implode(", ", $allowedExtensions)]
                            )
                        ];
                        continue;
                    }
                    $replaceFile = $emptyNewFile::getById($parameters['replaceId'] ?? 0, withChilds: true);
                    if ($replaceFile) {
                        $fileId = $replaceFile->id;
                        $replaceFile->store(false, $uploadedFile);
                    } else {
                        $mediaFile = $emptyNewFile->clone();
                        $mediaFile->relativePathOnDisk = $relativePathOnDisk;
                        $mediaFile->storableFolder = $currentFolder;
                        $mediaFile->store(false, $uploadedFile);
                        $fileId = $mediaFile->id;
                    }
                    $jsCall->result = [
                        'type' => 'success',
                        'message' => Lang::get('__framelix_mediabrowser_upload_done__', [$uploadedFile->name]),
                        'fileId' => $fileId
                    ];
                    return;
                }
            } catch (Throwable $e) {
                $jsCall->result = [
                    'type' => 'error',
                    'message' => Lang::get(
                        '__framelix_mediabrowser_upload_failed__',
                        [$uploadedFile->name, $e->getMessage()]
                    )
                ];
            }
            return;
        }
        $action = Request::getPost('action') ?? $jsCall->parameters['action'] ?? $jsCall->action;
        switch ($action) {
            case 'metadata':
                $file = $emptyNewFile::getById((int)($jsCall->parameters['id'] ?? 0), withChilds: true);
                if (!$file) {
                    return;
                }
                if ($rootFolder) {
                    $parent = $file->storableFolder;
                    $found = false;
                    while ($parent) {
                        if ($parent === $rootFolder) {
                            $found = true;
                            break;
                        }
                        $parent = $parent->parent;
                    }
                    if (!$found) {
                        return;
                    }
                }
                $data = [
                    'url' => $file->getPublicUrl()
                ];
                if ($file->isImage()) {
                    $data['imageTag'] = $file->getImageTag();
                }
                if ($file->isThumbnailable()) {
                    foreach ($file->thumbSizes as $thumbSize) {
                        $data['thumb-' . $thumbSize] = $file->getPublicUrl($thumbSize);
                    }
                }
                $jsCall->result = $data;
                break;
            case 'edit-selection-info':
                $selection = MediaBrowserSelection::create($jsCall->parameters['value'] ?? null);
                $selectionData = $selection?->getSelectionFoldersAndFiles();
                if ($selectionData['files'] ?? null) {
                    echo '<framelix-alert>__framelix_mediabrowser_edit_selection_info_sort__</framelix-alert>';
                    echo '<div class="framelix-mediabrowser-edit-sort-container">';
                    $sort = 1;
                    foreach ($selectionData['files'] as $file) {
                        echo '<div class="framelix-mediabrowser-edit-sort" data-id="' . $file . '">';
                        echo $file->getMediaBrowserThumbnailHtml();
                        echo '<div><input type="number" value="' . $sort . '" step="1" class="framelix-form-field-input"></div>';
                        echo '</div>';
                        $sort++;
                    }
                    echo '</div>';
                }
                break;
            case 'selectioninfo':
                $selection = MediaBrowserSelection::create($jsCall->parameters['value'] ?? null);
                $selectionData = $selection?->getSelectionFoldersAndFiles();
                $result = Lang::get('__framelix_mediabrowser_noselection__');
                if ($selectionData) {
                    if (count($selectionData['files']) === 1) {
                        $file = reset($selectionData['files']);
                        $result = Lang::get('__framelix_mediabrowser_selectioninfo_single__', [$file->filename]);
                    } else {
                        $result = '';
                        if (!$disabled) {
                            $result .= '<framelix-button small icon="sort" data-action="edit-selection-info">__framelix_mediabrowser_edit_selection_info__</framelix-button>&nbsp;';
                        }
                        $result .= Lang::get(
                            '__framelix_mediabrowser_selectioninfo__',
                            [count($selectionData['files']), $selectionData['foldersCount']]
                        );
                    }
                    if (!$disabled) {
                        $result = '<framelix-button small theme="error" icon="delete" data-action="unset-selection" title="__framelix_mediabrowser_selection_unset__"></framelix-button>&nbsp;' . $result;
                    }
                }
                $maxThumbs = (int)($jsCall->parameters['selectionInfoMaxThumbs'] ?? 0);
                if ($maxThumbs > 0 && $selectionData) {
                    $selectionData = $selection->getSelectionFoldersAndFiles();
                    if ($selectionData['files']) {
                        $result .= '<div class="framelix-mediabrowser-selected-thumbnails">';
                        $count = 1;
                        foreach ($selectionData['files'] as $file) {
                            $rest = count($selectionData['files']) - $count;
                            $result .= $file->getMediaBrowserThumbnailHtml();
                            if ($maxThumbs <= $count && $rest > 0) {
                                $result .= '<div class="framelix-mediabrowser-thumbnail framelix-mediabrowser-thumbnail-more"><span>' . Lang::get(
                                        '__framelix_mediabrowser_selection_more__',
                                        [$rest]
                                    ) . '</span></div>';
                                break;
                            }
                            $count++;
                        }
                        $result .= '</div><div style="clear: both"></div>';
                    }
                }
                $jsCall->result = $result;
                break;
            case 'edit-file-list':
                $file = $emptyNewFile::getById($jsCall->parameters['id'] ?? 0, withChilds: true);
                if (!$file) {
                    echo "NotFound";
                    return;
                }
                ?>
                <framelix-button block data-action="rename" data-id="<?= $file ?>"
                                 data-name='<?= JsonUtils::encode($file->filename) ?>' theme="light" icon="edit"
                                 data-store-url="<?= JsCall::getUrl(
                                     __CLASS__,
                                     'edit-file-save',
                                     ['id' => $file, 'fileClass' => $fileClass]
                                 ) ?>">
                    __framelix_mediabrowser_rename__
                </framelix-button>
                <framelix-button block data-action="delete"
                                 data-confirm-message="<?= Lang::get('__framelix_delete_sure__') ?>" theme="error"
                                 icon="delete"
                                 data-delete-url="<?= JsCall::getUrl(
                                     __CLASS__,
                                     'delete-file',
                                     ['id' => $file, 'fileClass' => $fileClass]
                                 ) ?>">__framelix_deleteentry__
                </framelix-button>
                <?php
                break;
            case 'edit-file-save':
                $file = $emptyNewFile::getById(Request::getGet('id'), withChilds: true);
                $file->filename = $jsCall->parameters['value'] ?? null;
                $file->store();
                break;
            case 'edit-folder-list':
                $folder = StorableFolder::getById($jsCall->parameters['id'] ?? null);
                if (!$folder) {
                    echo "NotFound";
                    return;
                }
                $childs = $folder->getChilds(true);
                $files = 0;
                $folders = 1;
                foreach ($childs as $child) {
                    if ($child instanceof StorableFile) {
                        $files++;
                    } else {
                        $folders++;
                    }
                }
                ?>
                <framelix-button block data-action="rename" data-id="<?= $folder ?>"
                                 data-name='<?= JsonUtils::encode($folder->name) ?>' theme="light" icon="edit"
                                 data-store-url="<?= JsCall::getUrl(
                                     __CLASS__,
                                     'edit-folder-save',
                                     ['id' => $folder, 'fileClass' => $fileClass]
                                 ) ?>">
                    __framelix_mediabrowser_rename__
                </framelix-button>
                <framelix-button block data-action="delete"
                                 data-confirm-message="<?= Lang::get('__framelix_delete_sure__') ?><br/><?= Lang::get(
                                     '__framelix_mediabrowser_selectioninfo__',
                                     [$files, $folders]
                                 ) ?>" theme="error" icon="delete"
                                 data-delete-url="<?= JsCall::getUrl(
                                     __CLASS__,
                                     'delete-folder',
                                     ['id' => $folder, 'fileClass' => $fileClass]
                                 ) ?>">__framelix_deleteentry__
                </framelix-button>
                <?php
                break;
            case 'createfolder':
                $newFolder = new StorableFolder();
                $newFolder->parent = $currentFolder;
                $newFolder->name = trim($jsCall->parameters['value'] ?? '');
                $newFolder->store();
                $jsCall->result = ['type' => 'success', 'folderId' => $newFolder->id];
                break;
            case 'delete-folder':
                $folder = StorableFolder::getById(Request::getGet('id'));
                $folder->delete();
                break;
            case 'delete-file':
                $file = $emptyNewFile::getById(Request::getGet('id'), withChilds: true);
                $file->delete();
                break;
            case 'edit-folder-save':
                $folder = StorableFolder::getById(Request::getGet('id'));
                $folder->name = $jsCall->parameters['value'] ?? null;
                $folder->store();
                break;
            case 'browser':
                ?>
                <div class="framelix-mediabrowser-left-window">
                    <div class="framelix-mediabrowser-tree">
                        <?php
                        self::showFolderTree($rootFolder, $currentFolder, '__framelix_mediabrowser_rootfolder__');
                        ?>
                    </div>
                </div>
                <div class="framelix-mediabrowser-right-window">
                    <div class="framelix-mediabrowser-breadcrumps">
                    </div>
                    <div class="framelix-mediabrowser-actions">
                        <?php
                        if (!$disabled) {
                            $uploadField = new File();
                            $uploadField->name = "upload";
                            $uploadField->multiple = true;
                            $uploadField->show();
                            ?>
                            <framelix-button theme="primary" icon="add"
                                             data-action="createfolder"><?= Lang::get(
                                    '__framelix_mediabrowser_createfolder__'
                                ) ?></framelix-button>
                            <?php
                        }
                        ?>
                        <label class="framelix-mediabrowser-view">
                            <span class="material-icons">zoom_out</span>
                            <input type="range" min="0.1" max="1" value="0.1" step="0.1">
                            <span class="material-icons">zoom_in</span>
                        </label>
                    </div>
                    <?= '<div class="framelix-mediabrowser-scrollbody">' ?>
                    <div class="framelix-mediabrowser-files hidden">
                        <?php
                        $folders = [];
                        if ($currentFolder && (!$rootFolder || $rootFolder !== $currentFolder)) {
                            $folders["parent"] = $currentFolder->parent ?? new StorableFolder();
                            $folders["parent"]->name = "..";
                        }
                        /** @var StorableFolder[] $folders */
                        $folders = ArrayUtils::merge(
                            $folders,
                            StorableFolder::getByCondition(
                                $currentFolder ? 'parent = {0}' : 'parent IS NULL',
                                [$currentFolder],
                                ['+name']
                            )
                        );
                        $files = $emptyNewFile::getByCondition(
                            $currentFolder ? 'storableFolder = {0}' : 'storableFolder IS NULL',
                            [$currentFolder],
                            ['+filename'],
                            withChilds: true
                        );
                        $table = new Table();
                        $table->createHeader([
                            'action1' => '',
                            'action2' => '',
                            'action3' => '',
                            'thumbnail' => '',
                            'name' => Lang::get('__framelix_mediabrowser_foldername__') . "/" . Lang::get(
                                    '__framelix_mediabrowser_filename__'
                                ),
                            'timestamp' => '__framelix_modified_timestamp__',
                        ]);
                        foreach ($folders as $folder) {
                            $thumbnailStr = $folder->getMediaBrowserThumbnailHtml();
                            $thumbnailCell = new TableCell();
                            $thumbnailCell->stringValue = $thumbnailStr;

                            $action1 = $action2 = $action3 = null;
                            if (!$disabled) {
                                if ($multiple) {
                                    $action1 = new TableCell();
                                    $action1->button = true;
                                    $action1->buttonIcon = 'check';
                                    $action1->buttonTooltip = '__framelix_mediabrowser_select__';
                                    $action1->buttonAttributes = HtmlAttributes::create(['data-action' => 'select']);
                                }
                                $action2 = new TableCell();
                                $action2->button = true;
                                $action2->buttonIcon = 'edit';
                                $action2->buttonTooltip = '__framelix_edit__';
                                $action2->buttonAttributes = HtmlAttributes::create(['data-action' => 'edit-folder']);
                            }

                            $rowId = $table->createRow([
                                'action1' => $action1,
                                'action2' => $action2,
                                'action3' => $action3,
                                'thumbnail' => $thumbnailCell,
                                'name' => $folder->name,
                                'timestamp' => $folder->getModifiedTimestampTableCell(),
                            ]);
                            $table->getRowHtmlAttributes($rowId)->set('data-id', '0');
                            if ($folder->id) {
                                $table->getRowHtmlAttributes($rowId)->set('data-id', $folder->id);
                            }
                            $table->getRowHtmlAttributes($rowId)->set('data-action', 'openfolder');
                            ?>
                            <div tabindex="0" class="framelix-mediabrowser-entry framelix-mediabrowser-folder"
                                 data-id="<?= $folder ?>"
                                 data-action="openfolder">
                                <?= $thumbnailStr ?>
                                <div class="framelix-mediabrowser-label"><?= HtmlUtils::escape($folder->name) ?></div>
                                <?php
                                if (!$disabled && $folder->id) {
                                    ?>
                                    <div class="framelix-mediabrowser-entry-actions">
                                        <framelix-button block icon="check" data-action="select"
                                                         title="__framelix_mediabrowser_select__"></framelix-button>
                                        <framelix-button block icon="edit"
                                                         title="__framelix_edit__"
                                                         data-action="edit-folder"></framelix-button>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                        }

                        foreach ($files as $file) {
                            if (is_array($allowedExtensions) && !in_array($file->extension, $allowedExtensions)) {
                                continue;
                            }
                            $thumbnailStr = $file->getMediaBrowserThumbnailHtml();
                            $thumbnail = new TableCell();
                            $thumbnail->stringValue = $thumbnailStr;
                            $action1 = $action2 = $action3 = null;
                            if (!$disabled) {
                                $action1 = new TableCell();
                                $action1->button = true;
                                $action1->buttonIcon = 'check';
                                $action1->buttonTooltip = '__framelix_mediabrowser_select__';
                                $action1->buttonAttributes = HtmlAttributes::create(['data-action' => 'select']);
                                $action2 = new TableCell();
                                $action2->button = true;
                                $action2->buttonIcon = 'edit';
                                $action2->buttonTooltip = '__framelix_edit__';
                                $action2->buttonAttributes = HtmlAttributes::create(['data-action' => 'edit-file']);
                            }
                            $rowId = $table->createRow([
                                'action1' => $action1,
                                'action2' => $action2,
                                'action3' => $action3,
                                'thumbnail' => $thumbnail,
                                'name' => $file->filename,
                                'timestamp' => $file->getModifiedTimestampTableCell(),
                            ]);
                            $table->getRowHtmlAttributes($rowId)->set('data-id', $file->id);
                            $table->getRowHtmlAttributes($rowId)->set('data-action', 'select');
                            ?>
                            <div tabindex="0" class="framelix-mediabrowser-entry framelix-mediabrowser-file"
                                 data-id="<?= $file ?>" data-action="select">
                                <?= $thumbnailStr ?>
                                <div class="framelix-mediabrowser-label"><?= HtmlUtils::escape($file->filename) ?></div>
                                <?php
                                if (!$disabled) {
                                    ?>
                                    <div class="framelix-mediabrowser-entry-actions">
                                        <framelix-button icon="check" data-action="select"
                                                         title="__framelix_mediabrowser_select__"></framelix-button>
                                        <framelix-button icon="edit"
                                                         title="__framelix_edit__"
                                                         data-action="edit-file"></framelix-button>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="framelix-mediabrowser-files-table hidden">
                        <?php
                        $table->addColumnFlag("action1", $table::COLUMNFLAG_REMOVE_IF_EMPTY);
                        $table->addColumnFlag("action2", $table::COLUMNFLAG_REMOVE_IF_EMPTY);
                        $table->addColumnFlag("action3", $table::COLUMNFLAG_REMOVE_IF_EMPTY);
                        $table->addColumnFlag("thumbnail", $table::COLUMNFLAG_SMALLWIDTH);
                        $table->addColumnFlag("timestamp", $table::COLUMNFLAG_SMALLFONT);
                        $table->show();
                        ?>
                    </div>
                    <?= '</div>' ?>
                    <div class="framelix-mediabrowser-selected-info">
                        <div class="framelix-loading"></div>
                    </div>
                </div>
                <?php
                break;
        }
    }

    /**
     * Show folder tree
     * @param StorableFolder|null $folder
     * @param StorableFolder|null $currentFolder
     * @param string|null $label Override folder label
     * @return void
     * @throws FatalError
     */
    public static function showFolderTree(
        ?StorableFolder $folder,
        ?StorableFolder $currentFolder,
        ?string $label = null
    ): void {
        $childs = StorableFolder::getByCondition($folder ? 'parent = {0}' : 'parent IS NULL', [$folder]);
        ?>
        <div class="framelix-mediabrowser-tree-entry">
            <framelix-button theme="light" class="framelix-mediabrowser-tree-label"
                             data-active="<?= $currentFolder === $folder ?>"
                             icon="folder" data-action="openfolder"
                             data-id="<?= $folder ?>"><?= $label ? Lang::get(
                    $label
                ) : ($folder->name ?? 0) ?></framelix-button>
            <?php
            if ($childs) {
                echo '<div class="framelix-mediabrowser-tree-childs">';
                foreach ($childs as $child) {
                    self::showFolderTree($child, $currentFolder);
                }
                echo '</div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Set default relative path
     * @param bool $public
     * @param string $module
     * @return void
     */
    public function setDefaultRelativePath(bool $public, string $module = FRAMELIX_MODULE): void
    {
        $this->relativePathOnDisk = $module . "/" . ($public ? "public" : "private") . "/storablefile";
    }

    /**
     * Set allowing only images for which we can create thumnails
     * @return void
     */
    public function setOnlyThumbnailableImages(): void
    {
        $this->allowedExtensions = StorableFile::THUMBNAIL_EXTENSIONS;
    }

    /**
     * Set allowing only images
     * @return void
     */
    public function setOnlyImages(): void
    {
        $this->allowedExtensions = StorableFile::IMAGE_EXTENSIONS;
    }

    /**
     * Set allowing only videos that can be played in the browser
     * @return void
     */
    public function setOnlyVideos(): void
    {
        $this->allowedExtensions = StorableFile::VIDEO_EXTENSIONS;
    }

    /**
     * Get converted submitted value
     * @return MediaBrowserSelection|null
     */
    public function getDefaultConvertedSubmittedValue(): MediaBrowserSelection|null
    {
        return MediaBrowserSelection::create($this->getSubmittedValue());
    }

    /**
     * Get jscall url for current field parameters
     * @return string
     */
    public function getJsCallUrl(): string
    {
        return JsCall::getUrl(
            __CLASS__,
            'browser',
            [
                'allowedExtensions' => $this->allowedExtensions,
                'relativePathOnDisk' => $this->relativePathOnDisk,
                'multiple' => $this->multiple,
                'rootFolder' => $this->rootFolder,
                'currentFolder' => $this->rootFolder,
                'disabled' => $this->disabled,
                'fileClass' => $this->fileClass
            ]
        );
    }


    public function jsonSerialize(): PhpToJsData
    {
        $data = parent::jsonSerialize();
        $data->properties['jsCallUrl'] = $this->getJsCallUrl();
        return $data;
    }
}