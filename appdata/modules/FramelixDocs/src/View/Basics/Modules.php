<?php

namespace Framelix\FramelixDocs\View\Basics;

use Framelix\Framelix\Html\CompilerFileBundle;
use Framelix\FramelixDocs\View\GetStarted\Setup;
use Framelix\FramelixDocs\View\View;

use const FRAMELIX_APPDATA_FOLDER;
use const FRAMELIX_USERDATA_FOLDER;

class Modules extends View
{
    protected string $pageTitle = 'Modules';

    public function showContent(): void
    {
        ?>
        <p>
            Modules in Framelix are basically a <code>App</code>. Each module can be a full application.
            It includes all source code and resources to serve an application.
            <?php
            echo $this->getAnchoredTitle('structure', 'Module structure');
            ?>
        <p>
            A module is a folder inside <code><?= FRAMELIX_APPDATA_FOLDER . "/modules" ?></code> where the folder name
            is the module name.
            The <code>FramelixStarter</code> module is integrated in every docker image, a starting point for each new
            module.
            You can goto <?= $this->getLinkToInternalPage(Setup::class) ?> for how to create a new module.<br/>
            The minimal file structure of a module is as followed (Beginning from the module folder):
        </p>
        <ul>
            <li>
                <b>_meta</b> - A folder that contain auto-generated metadata about several views and other things inside
                your module. It is mostly used as a cache, to save resources in production systems (Storing class data
                and so on...)
            </li>
            <li>
                <b>lang</b> - A folder that contains all translation <code>.json</code> files for your module. Framelix
                have multi-language
                support.
            </li>
            <li>
                <b>public</b> - This folder is the entry point for your application.
                It always contains a <code>index.php</code> file.
                Every request is forwarded to this file (Except for urls with a special prefix).<br/>
                Examples:
                <ul>
                    <li><strong>Default:</strong> <code>https://{appurl}/xyz</code> - Will be routed through index.php
                    </li>
                    <li><strong>Starting with __{modulename}</strong>: <code>https://{appurl}/__Framelix/test.png</code>
                        - Will serve
                        a file that is inside the userdata public folder. In this example it will serve a file from
                        <code><?= FRAMELIX_USERDATA_FOLDER . "/Framelix/public/test.png" ?></code><br/>
                        This can be used for all user generated files, like uploads.
                    </li>
                    <li><strong>Starting with _{modulename}</strong>:
                        <code>https://{appurl}/_Framelix/img/logo.png</code> - Will serve
                        a file that is inside the modules public folder. In this example in <code>Framelix/public/img/logo.png</code><br/>
                        This is used for resource file urls from module to be included in the browser (Images,
                        libraries, etc...)
                    </li>
                    <li><strong>Starting with ${modulename}</strong>:
                        <code>https://{appurl}/$Framelix/en.json</code> -
                        Will serve a file that is inside the modules lang folder. In this example in <code>Framelix/lang/en.json</code><br/>
                        This is used for language files to be available to be loaded async, when they are needed in the
                        frontend.
                    </li>
                </ul>
            </li>
            <li>
                <b>public/dist</b> - A folder that contains all auto-generated compiled source files, that are added
                with <?= $this->getSourceFileLinkTag([CompilerFileBundle::class]) ?>.
                This files are used to be included in the frontend (CSS and JS).
            </li>
            <li>
                <b>src</b> - A folder that contains all PHP source code for the module.
            </li>
            <li>
                <b>vendor-frontend</b> - A folder that contains all source files for the frontend code (JS/SCSS). It is
                not available directly in the browser, it is used as source for the SCSS/JS compilers.
            </li>
            <li><b>more folders</b> - You can add as many folders as you like. If you use composer for example, you will
                most like have a <code>vendor</code> folder. The <code>autoload.php</code> is automatically included,
                when there is one.
            </li>
        </ul>
        <?php
    }
}