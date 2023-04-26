<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\Console;
use Framelix\Framelix\Cron;
use Framelix\Framelix\Utils\Mutex;
use Framelix\FramelixDocs\View\View;

class Cronjobs extends View
{
    protected string $pageTitle = 'Cronjobs - Scheduled code execution';

    public function showContent(): void
    {
        ?>
        <p>
            Framelix have a built cronjob scheduler, that fires every 5 minutes.
            You can create your own <?= $this->getSourceFileLinkTag([Cron::class]) ?> class by extending the Console
            class <?= $this->getSourceFileLinkTag([Console::class]) ?> and by integrating the function
            <code>runCron</code>, as in this example.
        </p>
        <p>
            The core itself have some default cronjobs, like database backups and removing old event logs.
        </p>
        <p>
            As the job runs every 5 minutes, you surely need a way to say that it should do something only once a day,
            or every 3 hours, and so on.
            For this, we have a <?= $this->getSourceFileLinkTag([Mutex::class]) ?> which basically hold a
            <code>Mutex</code>
            for a given time period and then releases the Mutex automatically after that time.
        </p>
        <p>
            All code that runs with this method is executed under the <code>command line</code>, independent of a
            frontend. Command line execution have unlimited lifetime by default, so you can run really heavy tasks in
            this process.
        </p>
        <p>
            For example, we internally use this, to execute code only once every hour with this snippet.
        </p>
        <?php
        $this->startCodeRecording(function () {
            if (!Mutex::isLocked('framelix-hourly-cron', 3600)) {
                Mutex::create('framelix-hourly-cron');
                // and this only fires once a day at 03:00 in the morning
                if ((int)date("H") === 3) {
                    // make backups...
                }
            }
        });
        $this->showRecordedCode();
    }
}