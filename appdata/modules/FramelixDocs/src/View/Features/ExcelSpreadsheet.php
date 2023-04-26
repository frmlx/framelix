<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Utils\SpreadsheetWrapper;
use Framelix\FramelixDocs\View\View;

class ExcelSpreadsheet extends View
{
    protected string $pageTitle = 'Spreadsheet/Excel';

    public static function download(): void
    {
        $instance = SpreadsheetWrapper::create();
        $arr = [
            [
                'id' => 'ID',
                'name' => 'Name',
                'timestamp' => 'Timestamp',
            ]
        ];
        for ($i = 1; $i <= 30; $i++) {
            $arr[] = [
                'id' => $i,
                'name' => 'My number name is ' . $i,
                'timestamp' => DateTime::create('now + ' . $i . ' days'),
            ];
        }
        $instance->setFromArray($arr, autoFilterRange: "A1:*1");
        $instance->download('you-got-a-framelix-spreadsheet.xlsx');
    }

    public function showContent(): void
    {
        ?>
        <p>
            Framelix have
            integrated <?= $this->getLinkToExternalPage(
                'https://phpspreadsheet.readthedocs.io/en/latest/',
                'PhpSpreadsheet'
            ) ?>, a tool to generate spreadsheet files (for Excel, OpenOffice, etc...)
        </p>
        <p>
            We have a small wrapper around it, for most common
            features. <?= $this->getSourceFileLinkTag([SpreadsheetWrapper::class]) ?>
        </p>
        <?php

        $this->addPhpExecutableMethod([__CLASS__, "download"],
            "Basic spreadsheet",
            "A basic spreadsheet file out of a PHP array. Notice that the timestamp in the data is UTC, as all times are default UTC, except times displayed in the frontend with the proper HTML Tag (which are user device based)."
        );
        $this->showPhpExecutableMethodsCodeBlock();
    }
}