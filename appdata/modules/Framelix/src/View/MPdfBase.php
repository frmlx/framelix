<?php

namespace Framelix\Framelix\View;

use Framelix\Framelix\Exception\SoftError;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\View\Backend\View;
use JetBrains\PhpStorm\ExpectedValues;
use Mpdf\Config\ConfigVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Mpdf\QrCode\QrCode;

use function is_dir;
use function mkdir;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;

abstract class MPdfBase extends View
{
    /**
     * The x position where the address must start in case of a windowed post cuvert
     */
    public const ADDRESS_HEADER_WINDOW_START_X_POSITION = 20;

    /**
     * The y position where the address must start in case of a windowed post cuvert
     */
    public const ADDRESS_HEADER_WINDOW_START_Y_POSITION = 55;

    public Mpdf $pdf;

    /**
     * Initialize the mpdf instance
     * @param string $defaultFont
     * @param int $defaultFontSize
     * @param string $orientation
     * @param string $format
     * @param int $marginLeft
     * @param int $marginTop
     * @param int $marginRight
     * @return void
     */
    public function init(
        #[ExpectedValues(values: [
            "helvetica",
            "dejavusans",
            "dejavuserif",
            "dejavuserifcondensed",
            "dejavusansmono",
            "freesans",
            "freeserif",
            "freemono"
        ])]
        string $defaultFont = 'helvetica',
        int $defaultFontSize = 12,
        string $orientation = "P",
        string $format = "A4",
        int $marginLeft = 20,
        int $marginTop = 20,
        int $marginRight = 20
    ): void {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $tmpDir = '/framelix/userdata/tmp/mpdf';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, recursive: true);
        }
        $this->pdf = new Mpdf([
            'tempDir' => $tmpDir,
            'fontDir' => array_merge($fontDirs, [
                __DIR__ . "/../../vendor/mpdf/mpdf/data/font",
            ]),
            'default_font' => $defaultFont,
            'default_font_size' => $defaultFontSize,
            'margin_left' => $marginLeft,
            'margin_top' => $marginTop,
            'margin_right' => $marginRight,
            'orientation' => $orientation,
            'format' => $format,
            'adjustFontDescLineheight' => 1.1,
        ]);
        ob_start();
        $this->showHeaderHtml();
        $html = ob_get_contents();
        ob_end_clean();
        if ($html) {
            $this->pdf->SetHTMLHeader($html);
        }

        ob_start();
        $this->showFooterHtml();
        $html = ob_get_contents();
        ob_end_clean();
        if ($html) {
            $this->pdf->SetHTMLFooter($html);
        }
    }

    public function onRequest(): void
    {
        $form = $this->getOptionsForm();
        if ($form->fields && !Form::isFormSubmitted($form->id)) {
            $this->showContentBasedOnRequestType();
            throw new SoftError();
        }
    }

    public function showContent(): void
    {
        $form = $this->getOptionsForm();
        $this->setOptionsFormFields($form);
        $form->addSubmitButton('go', '__framelix_ok__', 'east');
        $form->show();
    }

    /**
     * Draw QR Code
     * @param string $code
     * @param float $x
     * @param float $y
     * @param float $size
     * @param bool $withBorder
     * @return void
     */
    public function drawQrCode(string $code, float $x, float $y, float $size, bool $withBorder = false): void
    {
        $qrCode = new QrCode($code);
        if (!$withBorder) {
            $qrCode->disableBorder();
        }
        $output = new \Mpdf\QrCode\Output\Mpdf();
        $output->output($qrCode, $this->pdf, $x, $y, $size);
    }

    /**
     * Start a html output buffer
     */
    public function startHtml(): void
    {
        ob_start();
    }

    /**
     * Stop html buffer and write to pdf
     * @return void
     */
    public function stopHtmlAndWrite(): void
    {
        $out = ob_get_contents();
        ob_end_clean();
        $this->pdf->WriteHTML($out);
    }

    /**
     * Get a form that is displayed before the actual PDF is generated
     * The form is only displayed if fields are attached
     * Submit is done via post
     * @param Form $form
     */
    public function setOptionsFormFields(Form $form): void
    {
    }

    /**
     * Save the pdf to disk
     * @param string $filepath
     */
    public function saveToDisk(string $filepath): void
    {
        $this->pdf->Output($filepath, Destination::FILE);
    }

    /**
     * Get pdf filedata
     * @return string
     */
    public function getFiledata(): string
    {
        return $this->pdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * Just output PDF
     * @param string $filename
     * @return never
     */
    public function output(string $filename): never
    {
        $this->pdf->Output($filename, Destination::INLINE);
        throw new SoftError();
    }

    /**
     * Download the pdf
     * @param string $filename
     * @return never
     */
    public function download(string $filename): never
    {
        Response::download("@" . $this->getFiledata(), $filename);
    }

    /**
     * Show header html
     * Available placeholders in html text: {PAGENO} (Current Page), $this->pdf->aliasNbPg (Number of total pages), $this->pdf->aliasNbPgGp
     */
    public function showHeaderHtml(): void
    {
    }

    /**
     * Show footer html
     * Available placeholders in html text: {PAGENO} (Current Page), $this->pdf->aliasNbPg (Number of total pages), $this->pdf->aliasNbPgGp
     */
    public function showFooterHtml(): void
    {
    }

    /**
     * Get options form
     * @return Form
     */
    private function getOptionsForm(): Form
    {
        $form = new Form();
        $form->id = 'options';
        $this->setOptionsFormFields($form);
        return $form;
    }
}