<?php

namespace Framelix\Framelix\View\Backend\System;

use Framelix\Framelix\ErrorHandler;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;

use function clearstatcache;
use function str_ends_with;

use const SCANDIR_SORT_DESCENDING;

class ErrorLogs extends View
{
    protected string|bool $accessRole = "system";

    public function onRequest(): void
    {
        if (Request::getGet('clear')) {
            $files = FileUtils::getFiles(ErrorHandler::LOGFOLDER, sortOrder: SCANDIR_SORT_DESCENDING);
            FileUtils::deleteDirectory(ErrorHandler::LOGFOLDER);
            mkdir(ErrorHandler::LOGFOLDER, recursive: true);
            clearstatcache();
            Toast::success('Deleted ' . count($files) . ' logs');
            \Framelix\Framelix\View::getUrl(self::class)->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $files = FileUtils::getFiles(
            ErrorHandler::LOGFOLDER,
            sortOrder: SCANDIR_SORT_DESCENDING
        );
        if (!$files) {
            ?>
            <framelix-alert>__framelix_view_backend_system_logs_nologs__</framelix-alert>
            <?php
            return;
        }
        ?>
        <framelix-button href="<?= Url::create()->setParameter('clear', 1) ?>">
            __framelix_view_backend_system_logs_clear__
        </framelix-button>
        <div class="framelix-spacer"></div>
        <?php
        foreach ($files as $file) {
            if (str_ends_with($file, '.json')) {
                ErrorHandler::showErrorFromExceptionLog(JsonUtils::readFromFile($file), true);
            }
        }
    }
}