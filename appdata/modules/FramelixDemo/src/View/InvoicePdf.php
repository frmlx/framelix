<?php

namespace Framelix\FramelixDemo\View;

use Framelix\FramelixDemo\Config;
use Framelix\FramelixDemo\Storable\Invoice;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\StorableArray;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Framelix\View\MPdfBase;

use function var_dump;

class InvoicePdf extends MPdfBase
{
    protected string|bool $accessRole = true;
    private Invoice $storable;

    public function onRequest(): void
    {
        parent::onRequest();
        $this->storable = Invoice::getById(Request::getGet('id'));
        $invoiceCopies = $this->storable->getInvoiceCopies();
        $type = Request::getPost('type') ?? Request::getGet('type') ?? 'preview';
        if (str_starts_with($type, 'attachment-')) {
            if (isset($invoiceCopies[$type])) {
                $invoiceCopies[$type]->download();
            }
            $this->showInvalidUrlError();
        }
        if ($type === 'original' && $this->storable->attachment) {
            $this->storable->attachment->download();
        }
        $textBeforePositions = null;
        $textAfterPositions = null;
        if ($type === 'custom') {
            $textBeforePositions = Request::getPost('textBeforePosition');
            $textAfterPositions = Request::getPost('textAfterPosition');
        }
        $this->init(marginTop: 110);
        $this->pdf->shrink_tables_to_fit = 0;
        $this->startHtml();
        ?>
        <style>
          th {
            font-weight: bold;
            background-color: #e5e5e5;
            border-bottom: 0.1pt solid black;
            padding: 5pt;
          }

          .positions td {
            padding: 5pt;
          }

          .position-cell-0 {
            border: 0.1pt solid black;
          }
          .position-cell-1 {
            border: 0.1pt solid black;
          }
          .position-cell-2 {
            text-align: right;
            border: 0.1pt solid black;
          }
          .position-cell-3 {
            text-align: right;
            border: 0.1pt solid black;
          }
          .position-footer {
            border: none;
            border-bottom: 0.1pt solid black;
          }
          .position-last-row {
            border-bottom: none;
          }
          .position-last-row {
            font-size: 110%;
            border-bottom: none;
            font-weight: bold;
          }
        </style>
        <?php
        $this->stopHtmlAndWrite();

        $creator = $this->storable->creator;

        if ($textBeforePositions) {
            $this->pdf->WriteHTML(nl2br($textBeforePositions) . "<br/><br/>");
        }

        if ($this->storable->textBeforePosition) {
            $this->pdf->WriteHTML(nl2br($this->storable->textBeforePosition) . "<br/><br/>");
        }

        $values = [
            [
                '__framelixdemo_storable_invoice_positions_label_count__',
                'Position',
                '__framelixdemo_storable_invoice_positions_label_netsingle__',
                '__framelixdemo_storable_invoice_net_label__'
            ]
        ];
        $positions = $this->storable->getPositions();
        foreach ($positions as $position) {
            $values[] = [
                $position->count,
                $position->comment,
                NumberUtils::format($position->netSingle, 2) . " " . Config::$moneyUnit,
                NumberUtils::format($position->netSingle * $position->count, 2) . " " . Config::$moneyUnit
            ];
        }
        $footerStartAt = count($values) - 1;
        $values[] = [
            '',
            '<b>' . Lang::get('__framelixdemo_storable_invoice_net_label__') . '</b>',
            '',
            NumberUtils::format($this->storable->net, 2) . " " . Config::$moneyUnit
        ];
        $lastRow = count($values) - 1;

        $html = '<table class="positions">';
        $widths = [10, 50, 20, 20];
        foreach ($values as $key => $row) {
            $cellType = 'td';
            if (!$key) {
                $cellType = 'th';
                $html .= '<thead>';
            }
            $html .= '<tr>';
            $i = 0;
            foreach ($row as $value) {
                $class = 'position-cell-' . $i;
                if ($footerStartAt < $key) {
                    $class .= ' position-footer';
                }
                if ($key === $lastRow) {
                    $class .= ' position-last-row';
                }
                $html .= '<' . $cellType . ' class="' . $class . '" width="' . ($widths[$i]) . '%">' . Lang::get(
                        $value
                    ) . '</' . $cellType . '>';
                $i++;
            }
            $html .= ' </tr>';
            if (!$key) {
                $html .= '</thead><tbody>';
            }
        }
        $html .= '</tbody></table><br/><br/>';
        $this->pdf->WriteHTML($html);

        if ($this->storable->textAfterPosition) {
            $this->pdf->WriteHTML(nl2br($this->storable->textAfterPosition) . '<br/><br/>');
        }

        if ($this->storable->flagReverseCharge) {
            $this->pdf->WriteHTML(nl2br(Lang::get('__framelixdemo_storable_invoice_flagreversecharge__')) . '<br/><br/>');
        }

        if ($textAfterPositions) {
            $this->pdf->WriteHTML(nl2br($textAfterPositions) . "<br/><br/>");
        }

        if ($this->storable->datePaid) {
            $this->pdf->WriteHTML(
                Lang::get(
                    '__framelixdemo_pdf_invoice_paid__',
                    ['date' => $this->storable->datePaid->getRawTextString()]
                )
            );
        } elseif ($creator->accountName && $creator->iban && $creator->bic) {
            if ($this->pdf->y > 220) {
                $this->pdf->AddPage();
            }
            $this->pdf->WriteHTML(
                '<br/>' . Lang::get(
                    '__framelixdemo_storable_systemvalue_invoicecreator_accountname_label__'
                ) . ': ' . $creator->accountName . '<br/>IBAN: ' . $creator->iban . '<br/>BIC: ' . $creator->bic . '<br/><br/>' . Lang::get(
                    '__framelixdemo_pdf_invoice_qr__'
                ) . '.<br/>'
            );
            $code = 'BCD
001
1
SCT
' . $creator->bic . '
' . $creator->accountName . '
' . str_replace(" ", "", $creator->iban) . '
EUR' . $this->storable->net . '


' . $this->storable->invoiceNr;
            $this->drawQrCode($code, $this->pdf->x, $this->pdf->y + 5, 30, 30);
        }

        if ($type === 'preview' || $type === 'copy') {
            $this->output("invoice-" . $type . "-" . $this->storable->invoiceNr . ".pdf");
        }
        $attachment = new StorableFile();
        $attachment->setDefaultRelativePath(false);
        $attachment->assignedStorable = $this->storable;
        if ($type === 'original') {
            $attachment->filename = "invoice-original-" . $this->storable->invoiceNr . ".pdf";
            $attachment->store(false, $this->getFiledata());
            $this->storable->attachment = $attachment;
            $this->storable->store();
        } else {
            $attachment->filename = "invoice-" . $type . "-" . $this->storable->invoiceNr . ".pdf";
            $attachment->store(false, $this->getFiledata());
            $invoiceCopies['attachment-' . $attachment] = $attachment;
            StorableArray::setValues($this->storable, $invoiceCopies);
        }
        $attachment->getDownloadUrl()->redirect();
    }

    public function showHeaderHtml(): bool
    {
        $type = Request::getPost('type') ?? Request::getGet('type') ?? 'preview';
        $title = Request::getPost('title');
        $overlayMessage = null;
        if ($type === 'preview') {
            $overlayMessage = Lang::get('__framelixdemo_storable_invoice_preview_label__');
        }
        if ($type === 'copy') {
            $overlayMessage = Lang::get('__framelixdemo_storable_invoice_copy_label__');
        }
        echo '<div style="position:absolute; top:20mm; left:20mm;">';
        if ($this->storable->creator->invoiceHeader?->getPath()) {
            echo '<img src="' . $this->storable->creator->invoiceHeader->getPath() . '" style="width:170mm;">';
        } else {
            echo nl2br(
                $this->storable->creator->address . ($this->storable->creator->vatId ? Lang::get(
                        '__framelixdemo_storable_systemvalue_invoicecreator_uid_label__'
                    ) . ": " . $this->storable->creator->vatId : '')
            );
        }
        echo '</div>';

        echo '<div style="position: absolute; top: ' . self::ADDRESS_HEADER_WINDOW_START_Y_POSITION . 'mm">' . nl2br(
                $this->storable->receiver
            ) . '</div>';

        $values = [];
        if ($this->storable->receiverVatId) {
            $values['__framelixdemo_pdf_invoice_receiver_vatid__'] = $this->storable->receiverVatId;
        }
        if ($this->storable->category === Invoice::CATEGORY_INVOICE) {
            $values['__framelixdemo_storable_invoice_date_label__'] = $this->storable->date->getHtmlString();
            if ($this->storable->performancePeriod) {
                $values['__framelixdemo_storable_invoice_performanceperiod_label__'] = $this->storable->performancePeriod;
            }
        } else {
            $values['__framelixdemo_storable_invoice_date_label__'] = $this->storable->date->getHtmlString();
        }
        if ($values) {
            echo '<div style="position: absolute; right:20mm;  top: ' . self::ADDRESS_HEADER_WINDOW_START_Y_POSITION . 'mm;"><table style="font-size: 10pt;"><tbody>';
            foreach ($values as $key => $value) {
                echo '<tr>
                    <td style="padding-right: 10pt; border-bottom: 0.1pt solid #ccc; width:30mm">' . Lang::get($key) . '</td>
                    <td style="border-bottom: 0.1pt solid #ccc; width:30mm; ">' . $value . '</td>
                    </tr>';
            }
            echo '</tbody></table></div>';
        }
        echo '<div style="position: absolute; top: 90mm;"><h2>' . ($title ? $title . ": " : '') . Lang::get(
                '__framelixdemo_pdf_invoice_category_' . $this->storable->category . '_title__',
                ['nr' => $this->storable->invoiceNr]
            ) . '</h2></div>';
        if ($overlayMessage) {
            for ($i = 0; $i <= 7; $i++) {
                echo '<div style="position: absolute; width:170mm; text-align:center; top: ' . ($i * 30 + 30) . 'mm; font-size: 50px; color:#ccc; font-weight:bold">' . $overlayMessage . '</div>';
            }
        }
        return true;
    }

    public function showFooterHtml(): bool
    {
        if ($this->storable->creator->invoiceFooter?->getPath()) {
            echo '<img src="' . $this->storable->creator->invoiceFooter->getPath() . '" style="width:170mm;">';
        }
        echo '<div style="font-size: 9pt; color:#888888;">#' . $this->storable->invoiceNr . ' | {PAGENO}/' . $this->pdf->aliasNbPg . '</div>';
        return true;
    }
}