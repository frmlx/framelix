<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Network\JsCall;
use Framelix\FramelixDocs\Storable\SimpleDemoFile;
use Framelix\FramelixDocs\View\Index;
use Framelix\FramelixDocs\View\View;

class Layout extends View
{
    protected string $pageTitle = 'Integrated responsive backend layout';

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'info') {
            echo "Test content comes right from the backend. Time on server is " . DateTime::create(
                    'now'
                )->getRawTextString();
        }
    }

    public function showContent(): void
    {
        ?>
        <p>
            The layout you currently see is integrated in Framelix.
            It has a dark mode support (Click at the right top corner icon).
            The layout is self-made. It do not use external libraries like Bootstrap, Vue, or whatever.
            It's all bare-metal and is just made to serve the things that are really required.
            This reduce unused code and upgrade incompatibilites, as we are self responsible to keep the layout nice and
            robust.
        </p>
        <p>
            Even if this layout is the default, you can make pages without any default layout and starting from scratch.
            This is often used to build a nice landing pages and frontend for the product and keep all specific data
            management in the default layout.
        </p>
        <p>
            The <?= $this->getLinkToInternalPage(Index::class) ?> landing page is an example for this.
        </p>
        <p>
            All the features in the sidebar are made and displayed with the default layout.
        </p>
        <?php
        echo $this->getAnchoredTitle('components', 'Web Components - Custom Tags');
        ?>
        <p>
            We have some custom web-components, all starting with <code>&lt;framelix-</code> for the most common tasks,
            like displaying a button that do things on-click.
        </p>
        <p>
            A list of all web-components an there available attributes is
            in <?= $this->getSourceFileLinkTag(["Framelix/web-types/web-types.json"]) ?>
        </p>
        <p>
            Here are a few examples.
        </p>
        <?php
        echo $this->getAnchoredTitle('alert', 'Alert Box');
        $this->addHtmlExecutableSnippet(
            'Alert',
            'Alert boxes. A container with a specific background color, to stand out from normal textual content. For something interesting. Also it can be hidden by the user, if enabled.',
            /** @lang HTML */
            '
            <framelix-alert theme="warning" hidable="test-hide-1">
                This is some warning, but can be dismissed by the user.
            </framelix-alert>
            <framelix-alert theme="error" hidable="test-hide-2">
                Very important
            </framelix-alert>
            <framelix-alert hidable="test-hide-3">
                Hey, let\'s read this.
            </framelix-alert>
            <framelix-alert theme="success">
                You can\'t hide this.
            </framelix-alert>
            '
        );
        $this->showHtmlExecutableSnippetsCodeBlock();

        echo $this->getAnchoredTitle('button', 'Smart Buttons');
        $this->addHtmlExecutableSnippet(
            'Normalo Button',
            'A default button. When you click it, it will load a Modal Window with some backend response in it. But before, you must accept.',
            /** @lang HTML */
            '
            <framelix-button theme="error"
                             icon="759"
                             confirm-message="Are you sure?"
                             jscall-url="' . JsCall::getUrl(__CLASS__, 'info') . '"
                             target="attached"
                             block>
                There is some destructive action
            </framelix-button>
            '
        );
        $this->showHtmlExecutableSnippetsCodeBlock();

        $demoFiles = SimpleDemoFile::getByCondition();
        $html = '';
        foreach ($demoFiles as $demoFile) {
            $html .= '<h2>' . $demoFile->filename . '</h2>' . $demoFile->getImageTag();
        }

        echo $this->getAnchoredTitle('images', 'Smart Images');
        $this->addHtmlExecutableSnippet(
            'Special Image Tag',
            'A special image tag with lazy loading by default and multiple size attributes to fit in the best matching image depending on the container size. If you do decrease your browser window size, you will notice that it does pick lower resolution images, while the original is 2MB large.',
            /** @lang HTML */
            $html
        );
        $this->showHtmlExecutableSnippetsCodeBlock();

        echo $this->getAnchoredTitle('localtime', 'Local times in frontend');
        $this->addHtmlExecutableSnippet(
            'Time Tag',
            'A simple time tag, that display an internal DateTime in a user local time. You will probably notice the visible timezone difference, if you are not in a UTC timezone.',
            /** @lang HTML */
            DateTime::create('now')->getHtmlString()
        );
        $this->showHtmlExecutableSnippetsCodeBlock();
        ?>
        <?php
    }
}