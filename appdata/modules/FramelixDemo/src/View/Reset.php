<?php

namespace Framelix\FramelixDemo\View;

use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Html\TypeDefs\JsRequestOptions;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\View\Backend\View;
use Framelix\FramelixDemo\Console;

class Reset extends View
{
    protected string|bool $accessRole = "admin";

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'reset') {
            Console::cleanupDemoData();
            Toast::success('All data has been reset');
            \Framelix\Framelix\View::getUrl(Index::class)->redirect();
        }
    }

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        ?>
        <p><?= Lang::get('__framelixdemo_view_reset_info__') ?></p>
        <framelix-button request-options='<?= new JsRequestOptions(JsCall::getUrl(__CLASS__, 'reset')) ?>'
                         confirm-message="<?= Lang::get('__framelix_sure__') ?>"
                         theme="primary"
                         icon="785"><?= Lang::get('__framelixdemo_view_reset__') ?></framelix-button>
        <?php
    }
}