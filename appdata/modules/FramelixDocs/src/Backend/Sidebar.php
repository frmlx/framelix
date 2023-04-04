<?php

namespace Framelix\FramelixDocs\Backend;


use Framelix\FramelixDocs\View\Background\CodingStandards;
use Framelix\FramelixDocs\View\Background\Idea;
use Framelix\FramelixDocs\View\Background\Terminology;
use Framelix\FramelixDocs\View\Basics\Database;
use Framelix\FramelixDocs\View\CoreDev\Docker;
use Framelix\FramelixDocs\View\CoreDev\Framelix;
use Framelix\FramelixDocs\View\Database\SchemeGenerator;
use Framelix\FramelixDocs\View\Database\Storables;
use Framelix\FramelixDocs\View\Features\Forms;
use Framelix\FramelixDocs\View\Features\InlinePopup;
use Framelix\FramelixDocs\View\Features\ModalWindow;
use Framelix\FramelixDocs\View\Features\Tables;
use Framelix\FramelixDocs\View\Features\Toasts;
use Framelix\FramelixDocs\View\GetStarted\Setup;
use Framelix\FramelixDocs\View\GetStarted\SetupCoreDev;
use Framelix\FramelixDocs\View\Index;

class Sidebar extends \Framelix\Framelix\Backend\Sidebar
{
    public function showContent(): void
    {
        $this->startGroup('Get started', 'start', forceOpened: true);
        $this->addLink(Index::class, "Welcome");
        $this->addLink(Setup::class);
        $this->addLink(SetupCoreDev::class);
        $this->showHtmlForLinkData();

        $this->startGroup('Basics', 'sports_baseball', forceOpened: true);
        $this->addLink(Database::class);
        $this->showHtmlForLinkData();

        $this->startGroup('Database', 'database', forceOpened: true);
        $this->addLink(Storables::class, "Storables");
        $this->addLink(SchemeGenerator::class);
        $this->showHtmlForLinkData();

        $this->startGroup('Features', 'motion_blur', forceOpened: true);
        $this->addLink(ModalWindow::class);
        $this->addLink(InlinePopup::class);
        $this->addLink(Toasts::class);
        $this->addLink(Tables::class);
        $this->addLink(Forms::class);
        $this->showHtmlForLinkData();

        $this->startGroup('Core Development', 'hub', forceOpened: true);
        $this->addLink(Docker::class, "Docker Image");
        $this->addLink(Framelix::class, "Framelix");
        $this->showHtmlForLinkData();

        $this->startGroup('Framelix Background', 'background_replace', forceOpened: true);
        $this->addLink(Idea::class);
        $this->addLink(Terminology::class);
        $this->addLink(CodingStandards::class);
        $this->showHtmlForLinkData();
    }
}