<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Network\JsCall;
use Framelix\FramelixDocs\View\View;

use const FRAMELIX_APPDATA_FOLDER;

class InlinePopup extends View
{
    protected string $pageTitle = 'Inline Popup / Dropdown';

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
            With our Javascript class <code>FramelixPopup</code> you can create containers that are attached to a
            element.
            It will stay there, flips automatically when scrolling comes to the edges of the popup, and have other cool
            features. The most obvious example of this is <code title="Yep, that's an inline popup">"Tooltip" (Hover
                this to see what we mean)</code>.
            <br/>
            In Framelix we us this, well, to show tooltips and for several other features like date picker, select
            dropdowns, etc...
        </p>
        <?php
        $this->addJsExecutableSnippet(
            'Simple',
            'Open a popup attached to this code block.',
            /** @lang JavaScript */
            "FramelixPopup.show(codeBlock, 'A simple popup')"
        );
        $this->addJsExecutableSnippet(
            'Async',
            'Open a popup with contents loaded from an ajax request.',
            /** @lang JavaScript */
            "FramelixPopup.show(codeBlock, FramelixRequest.jsCall('" . JsCall::getUrl(
                __CLASS__,
                'modalContentTest'
            ) . "'))"
        );
        $this->addJsExecutableSnippet(
            'Stay in viewport',
            'You can scroll it out of screen, but it will still be visible (Used form form validation messages for example).',
            /** @lang JavaScript */
            "FramelixPopup.show(codeBlock, 'A simple popup always here', {stickInViewport: true})"
        );
        $this->addJsExecutableSnippet(
            'Colored',
            'Mimic the colors of the code block.',
            /** @lang JavaScript */
            "FramelixPopup.show(codeBlock, 'A simple popup', {color:codeBlock})"
        );
        $this->addJsExecutableSnippet(
            'Positioned to MousePos',
            'The popup opens at the position of the mouse, instead of target centered. This is what a tooltip does.',
            /** @lang JavaScript */
            "FramelixPopup.show(codeBlock, 'A simple popup', {offsetByMouseEvent: ev})"
        );
        $this->showJsExecutableSnippetsCodeBlock();

        echo $this->getAnchoredTitle('options', 'FramelixPopup options');
        ?>
        <p>
            There are many options for the <code>FramelixPopup.show</code> call. To modify, size, width, behaviour,
            style, contents, listen for events and many more. Here are the corresponding doc comments for that.
        </p>
        <?php
        $this->addSourceFile(
            FRAMELIX_APPDATA_FOLDER . "/modules/Framelix/public/dist/typedefs/PopupShowOptions.js",
            'FramelixPopupShowOptions'
        );
        $this->showSourceFiles();
        ?>
        <p>
            The complete source including other functions can be found
            at <?= $this->getSourceFileLinkTag(['Framelix/js/framelix-popup.js']) ?>
        </p>
        <?php
    }
}