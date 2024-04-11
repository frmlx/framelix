<?php

namespace Framelix\Framelix\View\Backend\System;

use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

class SystemEventLogs extends View
{
    protected string|bool $accessRole = "admin,system";

    public function onRequest(): void
    {
        if (Request::getGet('clear')) {
            $objects = SystemEventLog::getByCondition();
            Storable::deleteMultiple($objects);
            Toast::success('Deleted ' . count($objects) . ' logs');
            \Framelix\Framelix\View::getUrl(self::class)->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        if ($category = Request::getGet('category')) {
            $meta = new \Framelix\Framelix\StorableMeta\SystemEventLog(new SystemEventLog());
            $meta->showSearchAndTableInTabs(SystemEventLog::getByCondition('category = {0}', [$category]));
            return;
        }
        $logs = SystemEventLog::getByCondition(sort: "-id", limit: 2000);
        $logCategories = Sql::get()->fetchColumn(
            "SELECT DISTINCT(category) FROM `" . SystemEventLog::class . "` ORDER BY category"
        );
        if (!$logs) {
            ?>
            <framelix-alert theme="primary">__framelix_view_backend_system_logs_nologs__</framelix-alert>
            <?php
            return;
        }
        ?>
        <framelix-button href="<?= Url::create()->setParameter('clear', 1) ?>">
            __framelix_view_backend_system_logs_clear__
        </framelix-button>
        <div class="framelix-spacer"></div>
        <?php

        $tabs = new Tabs();
        foreach ($logCategories as $logCategory) {
            $tabs->addTab(
                'category-' . $logCategory,
                '__framelix_systemeventlog_' . $logCategory . '__',
                new self(),
                ['category' => $logCategory]
            );
        }
        $tabs->show();
    }
}