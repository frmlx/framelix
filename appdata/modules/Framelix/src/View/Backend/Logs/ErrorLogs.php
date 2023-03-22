<?php

namespace Framelix\Framelix\View\Backend\Logs;

use Framelix\Framelix\ErrorHandler;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;

use function basename;
use function clearstatcache;
use function reset;

use const SCANDIR_SORT_DESCENDING;

class ErrorLogs extends View
{
    protected string|bool $accessRole = "admin";

    public function onRequest(): void
    {
        if (Request::getGet('clear')) {
            $files = FileUtils::getFiles(ErrorHandler::LOGFOLDER, sortOrder: SCANDIR_SORT_DESCENDING);
            FileUtils::deleteDirectory(ErrorHandler::LOGFOLDER);
            mkdir(ErrorHandler::LOGFOLDER);
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
            <framelix-alert>__framelix_view_backend_logs_nologs__</framelix-alert>
            <?php
            return;
        }
        $firstFile = basename(reset($files));
        ?>
        <framelix-button href="<?= Url::create()->setParameter('clear', 1) ?>">__framelix_view_backend_logs_clear__
        </framelix-button>
        <?php
        foreach ($files as $file) {
            Buffer::start();
            require $file;
            $contents = Buffer::get();
            ErrorHandler::showErrorFromExceptionLog(JsonUtils::decode($contents), true);
        }
    }
}