<?php

namespace Framelix\FramelixDemo\View;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Form\Field\Date;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\StorableArray;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Framelix\Utils\SpreadsheetWrapper;
use Framelix\Framelix\View\Backend\View;
use Framelix\FramelixDemo\Config;
use Framelix\FramelixDemo\Storable\Fixation;
use Framelix\FramelixDemo\Storable\Income;
use Framelix\FramelixDemo\Storable\Invoice;
use Framelix\FramelixDemo\Storable\Outgoing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

use function ceil;
use function count;
use function is_array;
use function ksort;

class Reports extends View
{
    protected string|bool $accessRole = "admin,reports";

    public static function getReportSheet(
        \Framelix\Framelix\Date $dateFrom,
        \Framelix\Framelix\Date $dateTo
    ): SpreadsheetWrapper {
        $rangeLabel = $dateFrom->getRawTextString() . " - " . $dateTo->getRawTextString();
        $excelDataOutgoings = [
            [
                'nr' => Lang::get('__framelixdemo_storable_outgoing_receiptnumber_label__'),
                'date' => Lang::get('__framelixdemo_storable_outgoing_date_label__'),
                'comment' => Lang::get('__framelixdemo_storable_outgoing_comment_label__'),
                'outgoingCategory' => Lang::get('__framelixdemo_storable_outgoing_outgoingcategory_label__'),
                'summaryKeys' => Lang::get('__framelixdemo_storable_systemvalue_outgoingcategory_summarykeys_label__'),
                'net' => Lang::get('__framelixdemo_storable_outgoing_net_label__'),
                'netOperational' => Lang::get('__framelixdemo_storable_outgoing_netoperational_label__'),
                'operationalSharePercent' => Lang::get(
                    '__framelixdemo_storable_systemvalue_outgoingcategory_operationalsharepercent_label__'
                )
            ]
        ];
        $excelDataIncomes = [
            [
                'nr' => Lang::get('__framelixdemo_storable_income_receiptnumber_label__'),
                'date' => Lang::get('__framelixdemo_storable_income_date_label__'),
                'comment' => Lang::get('__framelixdemo_storable_income_comment_label__'),
                'incomeCategory' => Lang::get('__framelixdemo_storable_income_incomecategory_label__'),
                'summaryKeys' => Lang::get('__framelixdemo_storable_systemvalue_incomecategory_summarykeys_label__'),
                'net' => Lang::get('__framelixdemo_storable_income_net_label__')
            ]
        ];
        $excelDataProfitLoss = [
            [
                '',
                Lang::get('__framelixdemo_view_outgoings__'),
                Lang::get('__framelixdemo_view_incomes__'),
                Lang::get('__framelixdemo_view_report_profitloss__')
            ]
        ];
        $excelDataReverseCharge = [
            [
                Lang::get('__framelixdemo_storable_invoice_receivervatid_label__'),
                Lang::get('__framelixdemo_view_report_total__')
            ]
        ];
        $range = \Framelix\Framelix\Date::rangeMonth($dateFrom, $dateTo);
        foreach ($range as $month) {
            $quarterMonthEnd = $month->dateTime->getQuarterEndMonth();
            $profitLossKeys[$month->dateTime->format("Ym01")] = $month->dateTime->getMonthNameAndYear();
            $profitLossKeys[$quarterMonthEnd->format("Ym99")] = $quarterMonthEnd->getQuarter() . ". " . Lang::get(
                    '__framelix_date_quarter__'
                ) . " " . $quarterMonthEnd->getYear();
            $profitLossKeys[$quarterMonthEnd->format("Y1301")] = $quarterMonthEnd->getYear();
        }
        ksort($profitLossKeys);
        foreach ($profitLossKeys as $key => $label) {
            $excelDataProfitLoss[$key] = [
                $label,
                0.0,
                0.0,
                0.0
            ];
        }
        $excelDataSummaryKeys = [
            [
                '',
                '',
                ''
            ]
        ];
        $outgoings = Outgoing::getByCondition('date BETWEEN {0} AND {1}', [$dateFrom, $dateTo], ['+date', '+id']);
        $incomes = Income::getByCondition('date BETWEEN {0} AND {1}', [$dateFrom, $dateTo], ['+date', '+id']);
        $invoicesReverseCharge = Invoice::getByCondition(
            'datePaid BETWEEN {0} AND {1} && LENGTH(receiverVatId)',
            [$dateFrom, $dateTo],
            ['+date', '+id']
        );
        foreach ($outgoings as $outgoing) {
            $summaryKeys = StorableArray::getValues($outgoing->outgoingCategory);
            $excelDataOutgoings[] = [
                'nr' => $outgoing->getReceiptNumber(),
                'date' => $outgoing->date,
                'comment' => $outgoing->comment,
                'outgoingCategory' => $outgoing->outgoingCategory,
                'summaryKeys' => $summaryKeys,
                'net' => $outgoing->net,
                'netOperational' => $outgoing->netOperational,
                'operationalSharePercent' => $outgoing->operationalSharePercent
            ];
            $quarterMonthEnd = $outgoing->date->dateTime->getQuarterEndMonth();
            $profitLossKeys = [
                $outgoing->date->dateTime->format("Ym01"),
                $quarterMonthEnd->format("Ym99"),
                $quarterMonthEnd->format("Y1301"),
            ];
            foreach ($profitLossKeys as $key) {
                $excelDataProfitLoss[$key][1] += $outgoing->netOperational;
                $excelDataProfitLoss[$key][3] -= $outgoing->netOperational;
            }
            foreach ($summaryKeys as $summaryKey) {
                if (!isset($excelDataSummaryKeys[$summaryKey->key])) {
                    $excelDataSummaryKeys[$summaryKey->key] = [
                        $summaryKey->key,
                        $summaryKey->name,
                        0.0
                    ];
                }
                $excelDataSummaryKeys[$summaryKey->key][2] += $summaryKey->getSummableNet($outgoing);
            }
        }
        foreach ($incomes as $income) {
            $summaryKeys = StorableArray::getValues($income->incomeCategory);
            $excelDataIncomes[] = [
                'nr' => $income->getReceiptNumber(),
                'date' => $income->date,
                'comment' => $income->comment,
                'incomeCategory' => $income->incomeCategory,
                'summaryKeys' => $summaryKeys,
                'net' => $income->net
            ];
            $quarterMonthEnd = $income->date->dateTime->getQuarterEndMonth();
            $profitLossKeys = [
                $income->date->dateTime->format("Ym01"),
                $quarterMonthEnd->format("Ym99"),
                $quarterMonthEnd->format("Y1301"),
            ];
            foreach ($profitLossKeys as $key) {
                $excelDataProfitLoss[$key][2] += $income->net;
                $excelDataProfitLoss[$key][3] += $income->net;
            }
            if (is_array($summaryKeys)) {
                foreach ($summaryKeys as $summaryKey) {
                    if (!isset($excelDataSummaryKeys[$summaryKey->key])) {
                        $excelDataSummaryKeys[$summaryKey->key] = [
                            $summaryKey->key,
                            $summaryKey->name,
                            0.0
                        ];
                    }
                    $excelDataSummaryKeys[$summaryKey->key][2] += $summaryKey->getSummableNet($income);
                }
            }
        }
        foreach ($invoicesReverseCharge as $invoice) {
            if (!isset($excelDataReverseCharge[$invoice->receiverVatId])) {
                $excelDataReverseCharge[$invoice->receiverVatId] = [
                    $invoice->receiverVatId,
                    0
                ];
            }
            $excelDataReverseCharge[$invoice->receiverVatId][1] += $invoice->net;
        }
        $rows = count($excelDataOutgoings);
        $excelDataOutgoings[] = [
            'net' => "=SUM(F1:F" . $rows . ")",
            'netOperational' => "=SUM(G1:G" . $rows . ")"
        ];
        $excelDataIncomes[]['net'] = "=SUM(F1:F" . count($excelDataIncomes) . ")";
        $excelData = [
            Lang::get('__framelixdemo_view_outgoings__') => $excelDataOutgoings,
            Lang::get('__framelixdemo_view_incomes__') => $excelDataIncomes,
            Lang::get('__framelixdemo_storable_systemvalue_summarykey__') => $excelDataSummaryKeys,
            Lang::get('__framelixdemo_view_report_profitloss__') => $excelDataProfitLoss,
            Lang::get('__framelixdemo_storable_invoice_flagreversecharge_label__') => $excelDataReverseCharge
        ];
        $spreadsheetWrapper = SpreadsheetWrapper::create();
        $spreadsheetWrapper->setFromArrayMultiple($excelData, true, "A1:*1");
        $widths = [
            [0, 8, 15, 30, 32, 15, 11, 11, 10],
            [0, 10, 15, 30, 35, 15, 11],
            [0, 30, 30, 30, 30],
            [0, 30, 30, 30, 30],
            [0, 30, 30]
        ];
        $sheetNr = 0;
        foreach ($excelData as $label => $rows) {
            $sheet = $spreadsheetWrapper->spreadsheet->getSheet($sheetNr);
            $lastColumn = $sheet->getHighestDataColumn();
            $sheetLabel = $label . ' | ' . $rangeLabel;
            $sheet->getHeaderFooter()
                ->setOddFooter('&L&B&R' . $sheetLabel . ' &P / &N');
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->insertNewRowBefore(1);
            $sheet->setCellValue("A1", $sheetLabel);
            $sheet->getStyle("A1")->getFont()->setSize(24)->setBold(true);

            $style = $sheet->getStyle("A2:{$lastColumn}2");
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f5f5f5');
            $style->getFont()->setBold(true);
            $style->getAlignment()->setWrapText(true);

            $style = $sheet->getStyle("A3:{$lastColumn}" . $sheet->getHighestDataRow());
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->mergeCells("A1:" . $lastColumn . "1");
            if (isset($widths[$sheetNr])) {
                foreach ($widths[$sheetNr] as $columnIndex => $width) {
                    $sheet->getColumnDimensionByColumn($columnIndex)->setWidth($width);
                }
            }

            $sheetNr++;
        }
        return $spreadsheetWrapper;
    }

    public function onRequest(): void
    {
        if (Request::getGet('excel')) {
            $dateFrom = \Framelix\Framelix\Date::create(Request::getGet('dateFrom'));
            $dateTo = \Framelix\Framelix\Date::create(Request::getGet('dateTo'));
            $spreadsheetWrapper = self::getReportSheet($dateFrom, $dateTo);
            $spreadsheetWrapper->download(
                'report-' . $dateFrom->getRawTextString() . "-" . $dateTo->getRawTextString() . ".xlsx"
            );
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $thisMonth = DateTime::create("now")->setDayOfMonth(1);
        $lastMonth = DateTime::create("now")->setDayOfMonth(1)->modify("- 1 month");
        $thisQuarter = DateTime::create("now")->getQuarterStartMonth();
        $lastQuarter = DateTime::create("now")->getQuarterStartMonth()->modify("-3 month");
        $thisYear = DateTime::create("now")->setMonth(1)->setDayOfMonth(1);
        $lastYear = DateTime::create("now")->setMonth(1)->setDayOfMonth(1)->modify("- 1 year");
        $dateRanges = [
            [
                "type" => "year",
                "label" => "thisyear",
                "dateFrom" => $thisYear,
                "dateTo" => $thisYear->clone()->setMonth(12)->setDayOfMonth(-1),
            ],
            [
                "type" => "year",
                "label" => "lastyear",
                "dateFrom" => $lastYear,
                "dateTo" => $lastYear->clone()->setMonth(12)->setDayOfMonth(-1),
            ],
            [
                "type" => "quarter",
                "label" => "thisquarter",
                "dateFrom" => $thisQuarter,
                "dateTo" => $thisQuarter->clone()->getQuarterEndMonth()->setDayOfMonth(-1),
            ],
            [
                "type" => "quarter",
                "label" => "lastquarter",
                "dateFrom" => $lastQuarter,
                "dateTo" => $lastQuarter->clone()->getQuarterEndMonth()->setDayOfMonth(-1),
            ],
            [
                "type" => "month",
                "label" => "thismonth",
                "dateFrom" => $thisMonth,
                "dateTo" => $thisMonth->clone()->setDayOfMonth(-1),
            ],
            [
                "type" => "month",
                "label" => "lastmonth",
                "dateFrom" => $lastMonth,
                "dateTo" => $lastMonth->clone()->setDayOfMonth(-1),
            ],
            [
                "type" => "custom",
                "label" => "custom"
            ]
        ];
        $rangeId = Request::getGet('index');
        if ($rangeId !== null && isset($dateRanges[$rangeId])) {
            $dateRange = $dateRanges[$rangeId];

            if ($dateRange['type'] === 'custom') {
                $form = $this->getForm();
                $form->addSubmitButton('report-charts', '__framelixdemo_view_reports_generate__', '727');
                $form->show();
            }

            if (isset($dateRange['dateFrom'])) {
                /** @var DateTime $dateFrom */
                $dateFrom = $dateRange['dateFrom'];
                $dateFrom = $dateFrom->cloneToDate();
                /** @var DateTime $dateTo */
                $dateTo = $dateRange['dateTo'];
                $dateTo = $dateTo->cloneToDate();
            }

            if (!isset($dateFrom) && Request::getGet('dateFrom')) {
                $dateFrom = \Framelix\Framelix\Date::create(Request::getGet('dateFrom'));
            }

            if (!isset($dateTo) && Request::getGet('dateTo')) {
                $dateTo = \Framelix\Framelix\Date::create(Request::getGet('dateTo'));
            }

            if (isset($dateFrom) && isset($dateTo)) {
                if ($dateRange['type'] === 'month') {
                    $dateFromPrev = $dateFrom->clone()->dateTime->modify("-1 month")->cloneToDate();
                    $dateToPrev = $dateTo->clone()->dateTime->modify("-1 month")->cloneToDate();
                }
                if ($dateRange['type'] === 'quarter') {
                    $dateFromPrev = $dateFrom->clone()->dateTime->modify("-3 month")->cloneToDate();
                    $dateToPrev = $dateTo->clone()->dateTime->modify("-3 month")->cloneToDate();
                }
                if ($dateRange['type'] === 'year') {
                    $dateFromPrev = $dateFrom->clone()->dateTime->modify("-1 year")->cloneToDate();
                    $dateToPrev = $dateTo->clone()->dateTime->modify("-1 year")->cloneToDate();
                }

                $cards = [
                    'profitloss' => [
                        'label' => '__framelixdemo_view_report_profitloss__',
                        'now' => 0.0,
                        'prev' => null,
                    ],
                    'incomes' => [
                        'label' => '__framelixdemo_view_incomes__',
                        'now' => 0.0,
                        'prev' => null,
                    ],
                    'outgoings' => [
                        'label' => '__framelixdemo_view_outgoings__',
                        'now' => 0.0,
                        'prev' => null,
                    ]
                ];

                $incomesNow = Income::getByCondition('date BETWEEN {0} AND {1}', [$dateFrom, $dateTo]);
                $outgoingsNow = Outgoing::getByCondition('date BETWEEN {0} AND {1}', [$dateFrom, $dateTo]);

                foreach ($incomesNow as $income) {
                    $cards['profitloss']['now'] += $income->net;
                    $cards['incomes']['now'] += $income->net;
                }
                foreach ($outgoingsNow as $outgoing) {
                    $cards['profitloss']['now'] -= $outgoing->netOperational;
                    $cards['outgoings']['now'] += $outgoing->netOperational;
                }

                if (isset($dateFromPrev) && isset($dateToPrev)) {
                    $incomesPrev = Income::getByCondition('date BETWEEN {0} AND {1}', [$dateFromPrev, $dateToPrev]);
                    $outgoingsPrev = Outgoing::getByCondition('date BETWEEN {0} AND {1}', [$dateFromPrev, $dateToPrev]);
                    $cards['profitloss']['prev'] = 0.0;
                    $cards['incomes']['prev'] = 0.0;
                    $cards['outgoings']['prev'] = 0.0;
                    foreach ($incomesPrev as $income) {
                        $cards['profitloss']['prev'] += $income->net;
                        $cards['incomes']['prev'] += $income->net;
                    }
                    foreach ($outgoingsPrev as $outgoing) {
                        $cards['profitloss']['prev'] -= $outgoing->netOperational;
                        $cards['outgoings']['prev'] += $outgoing->netOperational;
                    }
                }

                ?>
                <framelix-button href="<?= $this->getSelfUrl()
                    ->setParameter('dateFrom', $dateFrom)
                    ->setParameter('dateTo', $dateTo)
                    ->setParameter('excel', 1) ?>"
                                 theme="success"><?= Lang::get(
                        '__framelixdemo_view_reports_generate_excel__'
                    ) ?></framelix-button>
                <div class="framelix-spacer-x4"></div>
                <h2><?= $dateFrom->getHtmlString() ?> - <?= $dateTo->getHtmlString() ?></h2>
                <div class="report-cards">
                    <?php
                    foreach ($cards as $rowType => $row) {
                        $label = $row['label'];
                        $comparedTitle = '';
                        $diffLabel = '';
                        $diffLabelTooltip = '';
                        $icon = '707';
                        $color = '--color-error-text';
                        if (isset($dateFromPrev) && isset($dateToPrev)) {
                            $comparedTitle = Lang::get(
                                '__framelixdemo_view_report_compared_to__',
                                [$dateFromPrev->getHtmlString() . ' -  ' . $dateToPrev->getHtmlString()]
                            );
                            $diff = $row['now'] - $row['prev'];
                            $diffLabel = NumberUtils::format($diff, 2, plusSign: true);
                            $diffType = 'worse';
                            switch ($rowType) {
                                case 'profitloss':
                                    $label = '__framelixdemo_view_report_profit__';
                                    if ($diff > 0) {
                                        $diffType = 'better';
                                    }
                                    if ($row['now'] < 0) {
                                        $label = '__framelixdemo_view_report_loss__';
                                    }
                                    break;
                                case 'incomes':
                                    if ($diff > 0) {
                                        $diffType = 'better';
                                    }
                                    break;
                                case 'outgoings':
                                    $diffType = 'better';
                                    if ($diff > 0) {
                                        $diffType = 'worse';
                                    }
                                    break;
                            }
                            if ($rowType !== 'profitloss') {
                                $diffLabel .= Config::$moneyUnit . " <span class='report-amount-diff-info'>(" . NumberUtils::format(
                                        $row['now'] != 0 ? ceil((100 / abs($row['now']) * $diff)) : 0,
                                        plusSign: true
                                    ) . "%)</span>";
                            }
                            $diffLabelTooltip = '__framelixdemo_view_report_' . $diffType . '_' . $rowType . '__';
                            if ($diffType === 'better') {
                                $icon = '706';
                                $color = '--color-success-text';
                            }
                        }
                        ?>
                        <div class="report-card">
                            <h3><?= Lang::get($label) ?></h3>
                            <div class="report-amount">
                                <?php
                                if (isset($dateFromPrev) && isset($dateToPrev)) {
                                    ?>
                                    <div class="report-amount-diff" style="color: var(<?= $color ?>)">
                                        <div class="report-amount-diff-info"><?= Lang::get($diffLabelTooltip) ?></div>
                                        <?= HtmlUtils::getFramelixIcon($icon) ?>
                                        <?= $diffLabel ?>
                                        <div class="report-amount-diff-period">
                                            <?= $comparedTitle ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div class="report-amount-value">
                                    <?php
                                    if (isset($row['prev'])) {
                                        $number = NumberUtils::format($row['prev'], 2);
                                        if ($rowType === 'profitloss') {
                                            $number = NumberUtils::format($row['prev'], 2, plusSign: true);
                                        }
                                        ?>
                                        <div class="report-amount-diff-prev"
                                             title="<?= HtmlUtils::escape($comparedTitle) ?>">
                                            <?= $number . Config::$moneyUnit ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    <?php
                                    $number = NumberUtils::format($row['now'], 2);
                                    if ($rowType === 'profitloss') {
                                        $number = NumberUtils::format($row['now'], 2, plusSign: true);
                                        echo '<span style="color:var(' . ($row['now'] >= 0 ? '--color-success-text' : '--color-error-text') . ')">';
                                    }
                                    echo $number . Config::$moneyUnit;
                                    if ($rowType === 'profitloss') {
                                        echo '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
        } else {
            $tabs = new Tabs();
            foreach ($dateRanges as $i => $row) {
                if (isset($row['dateFrom'])) {
                    /** @var DateTime $dateFrom */
                    $dateFrom = $row['dateFrom'];
                    $dateFrom = $dateFrom->cloneToDate();
                    /** @var DateTime $dateTo */
                    $dateTo = $row['dateTo'];
                    $dateTo = $dateTo->cloneToDate();
                }
                if (isset($dateFrom) && isset($dateTo)) {
                    // check if data exist before adding the tab
                    $income = Income::getByConditionOne('date BETWEEN {0} AND {1}', [$dateFrom, $dateTo]);
                    if (!$income) {
                        $outgoing = Outgoing::getByConditionOne('date BETWEEN {0} AND {1}', [$dateFrom, $dateTo]);
                        if (!$outgoing) {
                            continue;
                        }
                    }
                }
                $tabs->addTab($i, "__framelixdemo_view_reports_" . $row['label'] . "__", new self(), ['index' => $i]);
            }
            $tabs->show();
        }
        ?>
        <style>
          .report-card {
            box-sizing: border-box;
            padding: 20px;
            border: 3px solid rgba(0, 0, 0, 0.2);
            margin: 5px;
            display: flex;
            flex-direction: column;
            max-width: 800px;
          }
          .report-card h3 {
            font-size: 30px;
          }
          .report-amount {
            display: flex;
            align-items: center;
            text-align: center;
            font-size: 30px;
            flex: 1 1 auto;
          }
          .report-amount-diff {
            flex-basis: 50%;
            font-size: 20px;
          }
          .report-amount-diff framelix-icon {
            font-size: 60px;
            display: block;
          }
          .report-amount-diff-period,
          .report-amount-diff-prev {
            font-size: 0.9rem;
            opacity: 0.9;
          }
          .report-amount-diff-info {
            font-size: 0.9rem;
          }
          .report-amount-value {
            flex-basis: 50%;
            text-align: center;
            font-weight: bold;
            background: rgba(0, 0, 0, 0.1);
            padding: 10px;
          }
        </style>
        <?php
    }

    private function getForm(): Form
    {
        $minMax = Fixation::getNextFixationDateRange();

        $form = new Form();
        $form->id = "report";
        $form->submitMethod = 'get';
        $form->submitAsync = false;

        $field = new Date();
        $field->name = "dateFrom";
        $field->label = "__framelixdemo_view_reports_datefrom__";
        $field->required = true;
        $field->defaultValue = Request::getGet($field->name) ?? $minMax[0];
        $form->addField($field);

        $field = new Date();
        $field->name = "dateTo";
        $field->label = "__framelixdemo_view_reports_dateto__";
        $field->required = true;
        $field->defaultValue = Request::getGet($field->name) ?? $minMax[1];
        $form->addField($field);

        return $form;
    }
}