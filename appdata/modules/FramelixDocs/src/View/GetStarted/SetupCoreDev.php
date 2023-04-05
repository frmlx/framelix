<?php

namespace Framelix\FramelixDocs\View\GetStarted;

use Framelix\FramelixDocs\View\View;

class SetupCoreDev extends View
{
    protected string $pageTitle = 'Setup for core development';

    public function showContent(): void
    {
        ?>
        <p>
            If you are into Framelix and really want to help improving the core, here is how you can start developing
            directly in the core, tests and docker image.
        </p>
        <?= $this->getAnchoredTitle('requirements', 'Requirements') ?>
        <p>
            Same as <?= $this->getLinkToInternalPage(Setup::class) ?>.
        </p>
        <?= $this->getAnchoredTitle('setup', 'Setup') ?>
        <p>
        <ol>
            <li>First, fork the repository
                on <?= $this->getLinkToExternalPage('https://github.com/NullixAT/framelix') ?>.
            </li>
            <li>Clone your forked repository somewhere to your host <code>git clone
                    https://github.com/{username}/framelix</code>
            <li>Change to that directory and run the following commands</li>
        </ol>
        <?php
        $this->showCodeBlock('
        cp dev-scripts/.env_template dev-scripts/.env
        bash dev-scripts/build-image.sh -t dev
        bash dev-scripts/start-container.sh -c
        bash dev-scripts/run-tests -t phpstan # PHP Stan Static Code Analysis 
        bash dev-scripts/run-tests -t playwright # Playwright End-to-End tests
        bash dev-scripts/run-tests -t phpunit # PHP Unit Tests
        ', downloadFilename: "framelix-coredev-install.sh");
        ?>
        <p>
            This will create 3 available apps/ports on your host.
        </p>
        <ul>
            <li><?= $this->getLinkToExternalPage('https://127.0.0.1:6101') ?> - The FramelixTests module which is used
                to run PhpUnit Tests on
            </li>
            <li><?= $this->getLinkToExternalPage('https://127.0.0.1:6102') ?> - The FramelixDocs module which is used to
                generate the docs you're currently reading
            </li>
            <li><?= $this->getLinkToExternalPage('https://127.0.0.1:6103') ?> - The FramelixStarter module which is used
                as a template for the module development setup
            </li>
        </ul>
        <?php
        echo $this->getAnchoredTitle('development', 'What and how to develop?');
        ?>
        <p>
            First and foremost, when you want to create new features, please first
            consider <?= $this->getLinkToExternalPage('https://github.com/NullixAT/framelix/discussions',
                'creating a discussion on Github') ?>. Maybe someone else is already working on similar things.
        </p>
        <p>
            As Framelix is a Full-Stack container, there are many things where you can develop. Improving the docker
            container itself. Improving the Framelix module core. Just working on some frontend stuff inside the core,
            etc... Pick the thing you like, you don't need to know everything to help us out.
        </p>
        <p>
            It is best, to just generally
            always <?= $this->getLinkToExternalPage('https://github.com/NullixAT/framelix/discussions', 'ask') ?> when
            you're stuck. This is kind of a new Framework. A general rule of thumb is not established yet. Just come
            over and discuss with us.
        </p>
        <?php
        echo $this->getAnchoredTitle('phpstorm', 'PhpStorm Setup for tests');
        ?>
        <p>
            We use PhpStorm IDE a lot, as it have cool features that make development faster and easier. One thing of it
            is, you can run Php Unit tests directly from contextmenu in PhpStorm, including CodeCoverage reports, which
            is super handy.
        </p>
        <p>
            To make this work, you need to set the following settings. Than you can right-click->Run on any PhpUnit test
            (or a whole folder) inside <code>FramelixTests/tests</code>.
        </p>
        <?php

        echo $this->getPublicResourceHtmlTag("images/phpstorm-cli-interpreter-docker-compose.png");
        echo $this->getPublicResourceHtmlTag("images/phpstorm-test-frameworks.png");
        echo $this->getPublicResourceHtmlTag("images/phpstorm-runtest.png");
    }
}