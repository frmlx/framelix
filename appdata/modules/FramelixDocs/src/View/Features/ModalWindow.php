<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Network\JsCall;
use Framelix\FramelixDocs\View\View;

use const FRAMELIX_APPDATA_FOLDER;

class ModalWindow extends View
{
    protected string $pageTitle = 'Modal/Dialog Window';

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
            With our Javascript class <code>FramelixModal</code> you can create Modal/Dialog windows easily.
            Modals in Framelix use the native HTML <code>&lt;dialog&gt;</code>, which have powerful features like making
            the side behind unusable, to focus on the modal.
            <br/>
            Modals can be stacked, so you can have many layers of them behind each other.<br/>
            Let's see a few example codes.
        </p>
        <?php
        $this->addJsExecutableSnippet(
            'Alert',
            'Open a alert box.',
            /** @lang JavaScript */
            "FramelixModal.alert('A simple alert box')"
        );
        $this->addJsExecutableSnippet(
            'Confirm',
            'Open a confirm box.',
            /** @lang JavaScript */
            "FramelixToast.success((await FramelixModal.confirm('A simple confirm box with 2 buttons to accept or decline').confirmed) ? 'Yep' : 'No')"
        );
        $this->addJsExecutableSnippet(
            'Prompt',
            'Open prompt to ask for user input.',
            /** @lang JavaScript */ "
            const result = await FramelixModal.prompt('A prompt window to show a user input form').promptResult
            if(result !== null) {
              FramelixToast.success('Accepted with answer: '+result)
            } else {
              FramelixToast.error('Not accepted')              
            }
        "
        );
        $this->addJsExecutableSnippet(
            'Stacked Modals',
            'Open multiple modals at once.',
            /** @lang JavaScript */
            "
              FramelixModal.alert('A simple alert box 1')
              FramelixModal.alert('A simple alert box 2')
              FramelixModal.alert('A simple alert box 3')
            "
        );
        $this->addJsExecutableSnippet(
            'Async',
            'With <code>FramelixRequest</code> you can pass a request object to the <code>.show</code> function, which loads the content from the request into the modal.',
            /** @lang JavaScript */
            "
              FramelixModal.show({
                  bodyContent: FramelixRequest.jsCall('" . JsCall::getUrl(
                __CLASS__,
                'modalContentTest'
            ) . "')
              })
            "
        );
        $this->showJsExecutableSnippetsCodeBlock();

        echo $this->getAnchoredTitle('options', 'FramelixModal options');
        ?>
        <p>
            There are many options for the <code>FramelixModal.show</code> call. To modify, size, width, behaviour,
            style, buttons, contents, listen for events and many more. Here are the corresponding doc
            comments for that.
        </p>
        <?php
        $this->addSourceFile(FRAMELIX_APPDATA_FOLDER . "/modules/Framelix/vendor-frontend/js/framelix-modal.js",
            'FramelixModalShowOptions');
        $this->showSourceFiles();
        ?>
        <p>
            The complete source including other functions can be found
            at <?= $this->getSourceFileLinkTag(['Framelix/js/framelix-modal.js']) ?>
        </p>
        <?php
    }
}