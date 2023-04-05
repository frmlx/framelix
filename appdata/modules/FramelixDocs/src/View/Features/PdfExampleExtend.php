<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\View\MPdfBase;

class PdfExampleExtend extends MPdfBase
{
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        parent::onRequest();
        $this->init();

        $this->startHtml();
        ?>
        <h1>Here we have some cool PDF Title</h1>
        <p>A lot of more text...</p>
        <?php
        $this->stopHtmlAndWrite();
        $this->pdf->AddPage();

        $this->startHtml();
        ?>
        <h1>Now on page 2</h1>
        <p>Easy, isn't it?</p>
        <?php
        $this->stopHtmlAndWrite();

        $this->output('you-got-a-framelix-pdf.pdf');
    }

    public function showHeaderHtml(): bool
    {
        echo '<div style="border-bottom: 1pt solid black">This is a header for every page</div>';
        return true;
    }

    public function showFooterHtml(): bool
    {
        echo '<div style="font-size: 9pt">This is the footer and page {PAGENO} of ' . $this->pdf->aliasNbPg . '</div>';
        return true;
    }
}