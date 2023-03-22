<?php

namespace Framelix\FramelixTests\View;

use Framelix\Framelix\View\Backend\View;

class ModalWindow extends View
{
    protected string|bool $accessRole = "*";
    protected string $pageTitle = 'Modal Window Tests';

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        ?>
        <framelix-button data-action="alert">Open Alert</framelix-button>
        <div class="framelix-spacer"></div>
        <framelix-button data-action="confirm">Open Confirm</framelix-button>
        <div class="framelix-spacer"></div>
        <framelix-button data-action="prompt">Open Prompt</framelix-button>
        <div class="framelix-spacer"></div>
        <script>
          (function () {
            $(document).on('click', 'framelix-button[data-action]', async function () {
              let modal
              switch (this.dataset.action) {
                case 'alert':
                  modal = FramelixModal.alert('Alert Test')
                  break
                case 'confirm':
                  modal = FramelixModal.confirm('Confirm Test')
                  break
                case 'prompt':
                  modal = FramelixModal.prompt('Prompt Test')
                  FramelixToast.success((await modal.promptResult) || 'Abort')
                  break
              }
            })
          })()
        </script>
        <?php
    }
}