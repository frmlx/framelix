<?php

namespace Framelix\FramelixTests\View\Misc;

use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;

class CustomElementButton extends View\Backend\View
{

    protected string|bool $accessRole = "*";

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'more-buttons') {
            for ($i = 5000; $i < 10000; $i++) {
                ?>
              <framelix-button icon="718" theme="<?= $i % 2 ? 'error' : 'success' ?>" style="font-family: monospace">
                Btn <?= sprintf("%04d", $i) ?>
              </framelix-button>
                <?php
            }
        }
    }

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        ?>
      <h3>Performance Test when displaying 5000 buttons and loading 5000 more dynamically</h3>
        <?php
        for ($i = 0; $i < 5000; $i++) {
            ?>
          <framelix-button icon="718" theme="<?= $i % 2 ? 'error' : 'success' ?>" style="font-family: monospace">
            Btn <?= sprintf("%04d", $i) ?>
          </framelix-button>
            <?php
        }
        ?>
      <div class="results"></div>
      <script>
        (async function () {
          const response = await FramelixRequest.jsCall(<?=JsonUtils::encode(
              JsCall::getSignedUrl([self::class, "onJsCall"], "more-buttons")
          )?>).getJson()
          $('.results')[0].innerHTML = response
        })()
      </script>
        <?php
    }

}