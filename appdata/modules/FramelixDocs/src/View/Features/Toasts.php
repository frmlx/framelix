<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Network\JsCall;
use Framelix\FramelixDocs\View\View;

class Toasts extends View
{
    protected string $pageTitle = 'Toasts/Notifications';

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'modalContentTest') {
            echo "Test content comes right from the backend. Time on server is "
                . DateTime::create('now')->getRawTextString();
        }
    }

    public function showContent(): void
    {
        ?>
        <p>
            With our Javascript class <code>FramelixToast</code> you can create small toasts/notifications at the bottom
            right of the screen.
            Toasts are used to display simple messages to an user, without layout shifts and in an always visible
            position.
            Toasts can be stacked. To keep toasts from taking up the whole screen, only one toast at a time is
            displayed. The user will see how many messages are in the queue.
        </p>
        <?php
        $this->addJsExecutableSnippet(
            'Success',
            'Just a simple success message.',
            /** @lang JavaScript */
            "FramelixToast.success('Wohoo, success')"
        );
        $this->addJsExecutableSnippet(
            'Error',
            'Error message in red.',
            /** @lang JavaScript */
            "FramelixToast.error('Hmm... Wrong')"
        );
        $this->addJsExecutableSnippet(
            'Warning',
            'A warning message and brown-ish color.',
            /** @lang JavaScript */
            "FramelixToast.warning('Maybe you should worry about this...')"
        );
        $this->addJsExecutableSnippet(
            'Neutral',
            'Just an information without any obvious precedence.',
            /** @lang JavaScript */
            "FramelixToast.info('Information message, neutral')"
        );
        $this->addJsExecutableSnippet(
            'Async',
            'Toasts can be async as well.',
            /** @lang JavaScript */
            "FramelixToast.info(FramelixRequest.jsCall('" . JsCall::getUrl(
                __CLASS__,
                'modalContentTest'
            ) . "'))"
        );
        $this->addJsExecutableSnippet(
            'Stacked',
            'Adding many at once to the queue.',
            /** @lang JavaScript */
            "
              for (let i = 0; i < 10; i++) {
                FramelixToast.info('Information message ' + i)
              }
            "
        );
        $this->showJsExecutableSnippetsCodeBlock();
        ?>
        <p>
            The complete source including other functions can be found
            at <?= $this->getSourceFileLinkTag(['Framelix/js/framelix-toast.js']) ?>
        </p>
        <?php
    }
}