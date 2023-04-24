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
        $this->addLink(Outgoings::class, icon: "704");
        $this->addLink(Incomes::class, icon: "705");
        $this->addLink(
            Invoices::class,
            '__framelixdemo_view_invoice_category_1__',
            icon: "73a",
            urlParameters: ['category' => 1]
        );
        $this->addLink(
            Invoices::class,
            '__framelixdemo_view_invoice_category_2__',
            icon: "73a",
            urlParameters: ['category' => 2]
        );
        $this->addLink(Depreciation::class, icon: "72f");
        $this->addLink(Fixations::class);
        $this->addLink(Reports::class, icon: "726");
        $this->showHtmlForLinkData();

        $this->addLink(Reset::class, icon: "704");
        $this->showHtmlForLinkData();

    }
}