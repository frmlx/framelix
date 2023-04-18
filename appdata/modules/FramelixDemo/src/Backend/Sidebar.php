<?php

namespace Framelix\FramelixDemo\Backend;

use Framelix\FramelixDemo\View\Depreciation;
use Framelix\FramelixDemo\View\Fixations;
use Framelix\FramelixDemo\View\Incomes;
use Framelix\FramelixDemo\View\Invoices;
use Framelix\FramelixDemo\View\Outgoings;
use Framelix\FramelixDemo\View\Reports;
use Framelix\FramelixDemo\View\Reset;

class Sidebar extends \Framelix\Framelix\Backend\Sidebar
{
    public function showContent(): void
    {
        $this->addLink(Outgoings::class, icon: "chevron_left");
        $this->addLink(Incomes::class, icon: "chevron_right");
        $this->addLink(
            Invoices::class,
            '__framelixdemo_view_invoice_category_1__',
            icon: "receipt",
            urlParameters: ['category' => 1]
        );
        $this->addLink(
            Invoices::class,
            '__framelixdemo_view_invoice_category_2__',
            icon: "description",
            urlParameters: ['category' => 2]
        );
        $this->addLink(Depreciation::class, icon: "receipt_long");
        $this->addLink(Fixations::class, icon: "push_pin");
        $this->addLink(Reports::class, icon: "leaderboard");
        $this->showHtmlForLinkData();

        $this->addLink(Reset::class, icon: "chevron_left");
        $this->showHtmlForLinkData();

    }
}