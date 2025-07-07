<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Utils\FastExcelWrapper;
use Framelix\Framelix\Utils\SpreadsheetWrapper;
use Framelix\FramelixDocs\View\View;

class ExcelSpreadsheet extends View
{

    protected string $pageTitle = 'Spreadsheet/Excel';

    public static function download(): void
    {
        $instance = new FastExcelWrapper();
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
        $instance->setFromArray($arr, 1);
        $instance->download('you-got-a-framelix-spreadsheet.xlsx');
    }

    public function showContent(): void
    {
        ?>
        <p>
            Framelix have
            integrated <?= $this->getLinkToExternalPage(
                'https://github.com/aVadim483/fast-excel-writer',
                'FastExcelWriter'
            ) ?>, a tool to generate spreadsheet files of any size for Excel, OpenOffice, etc...
        </p>
        <p>
            We have a small wrapper around it, for most common
            features. <?= $this->getSourceFileLinkTag([FastExcelWrapper::class]) ?>
        </p>
        <?php

        $this->addPhpExecutableMethod([__CLASS__, "download"],
            "Basic spreadsheet",
            "A basic spreadsheet file out of a PHP array. Notice that the timestamp in the data is UTC, as all times are default UTC, except times displayed in the frontend with the proper HTML Tag (which are user device based)."
        );
        $this->showPhpExecutableMethodsCodeBlock();
    }

}