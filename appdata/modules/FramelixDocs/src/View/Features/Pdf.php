<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\View\MPdfBase;
use Framelix\FramelixDocs\View\View;

class Pdf extends View
{
    protected string $pageTitle = 'PDF with MPDF';

    public static function download(): void
    {
        // generally you should extend from the MpdfBase, so you can link to that pdf page
        // for the case if this demo, we manually generate that view
        $instance = new PdfExampleExtend();
        $instance->init();

        $instance->startHtml();
        ?>
        <h1>Here we have some cool PDF Title</h1>
        <p>A lot of more text...</p>
        <?php
        $instance->stopHtmlAndWrite();
        $instance->download('you-got-a-framelix-pdf.pdf');
    }

    public function showContent(): void
    {
        ?>
        <p>
            Framelix have
            integrated <?= $this->getLinkToExternalPage(
                'https://mpdf.github.io/',
                'MPDF'
            ) ?>, a tool to generate PDF files.
        </p>
        <p>
            We have a view wrapped around it, with most common
            features <?= $this->getSourceFileLinkTag([MPdfBase::class]) ?>
        </p>
        <?php

        $this->addPhpExecutableMethod([__CLASS__, "download"],
            "Basic PDF",
            "A basic PDF file out of some HTML code.");
        $this->showPhpExecutableMethodsCodeBlock();
        ?>
        <p>
            The demo show an example, but real world usage is recommended by extending our <code>MpdfBase</code>
            view.<br/>
            Such a <?= $this->getLinkToInternalPage(PdfExampleExtend::class, 'live demo is here', true) ?>.
        </p>
        <?php
        $this->addSourceFile(PdfExampleExtend::class);
        $this->showSourceFiles();
    }
}