<?php

namespace Framelix\FramelixDocs\Backend;


use Framelix\FramelixDocs\View\Background\CodingStandards;
use Framelix\FramelixDocs\View\Background\Idea;
use Framelix\FramelixDocs\View\Background\Terminology;
use Framelix\FramelixDocs\View\Basics\Config;
use Framelix\FramelixDocs\View\Basics\Database;
use Framelix\FramelixDocs\View\Basics\Modules;
use Framelix\FramelixDocs\View\CoreDev\Docker;
use Framelix\FramelixDocs\View\CoreDev\Framelix;
use Framelix\FramelixDocs\View\Database\SchemeGenerator;
use Framelix\FramelixDocs\View\Database\Storables;
use Framelix\FramelixDocs\View\Features\Cronjobs;
use Framelix\FramelixDocs\View\Features\ExcelSpreadsheet;
use Framelix\FramelixDocs\View\Features\Forms;
use Framelix\FramelixDocs\View\Features\InlinePopup;
use Framelix\FramelixDocs\View\Features\Layout;
use Framelix\FramelixDocs\View\Features\ModalWindow;
use Framelix\FramelixDocs\View\Features\Pdf;
use Framelix\FramelixDocs\View\Features\StorableMeta;
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
        $this->addLink(Setup::class, ['Setup up for development', 'Usually where you should start']);
        $this->addLink(SetupCoreDev::class, ['Setup up for core development', 'Helping us with Framelix itself']);
        $this->showHtmlForLinkData();

        $this->startGroup('Basics', 'info', forceOpened: true);
        $this->addLink(Config::class, ['Configuration', 'Things like DB connections, secrets, etc...']);
        $this->addLink(Database::class, ['Database/Storables', 'Learn about relations between DB and Storables']);
        $this->addLink(Modules::class, ['Modules', 'The internal structure of Framelix']);
        $this->showHtmlForLinkData();

        $this->startGroup('Database', 'database', forceOpened: true);
        $this->addLink(Storables::class, ['Storables', 'Powerful management of your data']);
        $this->addLink(SchemeGenerator::class, ['Scheme Builder', 'Never need to worry about DB Scheme']);
        $this->showHtmlForLinkData();

        $this->startGroup('Features', 'motion_blur', forceOpened: true);
        $this->addLink(Layout::class, ['Default Layout', 'Fast, responsive, slick']);
        $this->addLink(ModalWindow::class, ['Modal/Dialog Window', 'Draw some content over the page']);
        $this->addLink(InlinePopup::class, ['Inline Popups and Dropdowns', 'Like a tooltip']);
        $this->addLink(Toasts::class, ['Toasts/Notifications', 'Show a short message for a short time']);
        $this->addLink(Tables::class, ['Tables', 'To display data in a table']);
        $this->addLink(Forms::class, ['Form Generator', 'Make user input forms with ease']);
        $this->addLink(ExcelSpreadsheet::class, ['Spreadsheet/Excel', 'Export data in this format']);
        $this->addLink(Pdf::class, ['PDF', 'Making and exporting documents']);
        $this->addLink(Cronjobs::class, ['Cronjobs', 'Schedule jobs for automatic execution']);
        $this->addLink(StorableMeta::class, ['StorableMeta', 'The well powered companion to Storables']);
        $this->showHtmlForLinkData();

        $this->startGroup('Core Development', 'hub', forceOpened: true);
        $this->addLink(Docker::class, ['Docker Image', 'The thing that serves all of Framelix']);
        $this->addLink(Framelix::class, ['Framelix', 'Hack into the core']);
        $this->showHtmlForLinkData();

        $this->startGroup('Framelix Background', 'background_replace', forceOpened: true);
        $this->addLink(Idea::class, ['The idea', 'Motivation and philosophy of Framelix']);
        $this->addLink(Terminology::class, ['Terminology', 'How things are named in Framelix']);
        $this->addLink(CodingStandards::class, ['Coding Standards', 'Rules for us and you']);
        $this->showHtmlForLinkData();
    }
}