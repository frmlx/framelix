<?php

namespace Framelix\FramelixDemo\View;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Utils\Mutex;
use Framelix\Framelix\View\Backend\View;
use Framelix\FramelixDemo\Cron;

class Index extends View
{
    protected string|bool $accessRole = true;

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $lifetimeRemains = Mutex::isLocked(Cron::CLEANUP_MUTEX_NAME, Cron::CLEANUP_MUTEX_LIFETIME);
        if ($lifetimeRemains > 0) {
            echo '<framelix-alert theme="warning">This app does reset all data every hour. Next data reset at  ' . DateTime::create(
                    'now + ' . $lifetimeRemains . ' seconds'
                )->getHtmlString() . '</framelix-alert>';
        }
        ?>
        <h1>Welcome to the Framelix Demo Application</h1>
        <p>
            This application as an example of what you can make with Framelix.
            The application is a copy of one our internal accounting software products that are build with Framelix.
        </p>
        <h2>The main features are</h2>
        <ul>
            <li>Manage incomes and outgoings for your company</li>
            <li>Manage and create PDF invoices and offers</li>
            <li>Excel reporting and exports</li>
            <li>Quick search features to find entries</li>
        </ul>
        <?php
    }
}