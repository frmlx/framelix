<?php

namespace Framelix\FramelixTests\tests\Utils;

use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FastExcelCell;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

final class FastExcelWrapper extends TestCase
{

    public function tests(): void
    {
        $wrapper = new \Framelix\Framelix\Utils\FastExcelWrapper();
        $wrapper->setFromArray([
            ["C1", "C2", "C3"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abcöäü", 123, "=1+2"],
        ], 1);
        Buffer::start();
        $this->assertExceptionOnCall(function () use ($wrapper) {
            $wrapper->download("test.xlsx");
        }, [], StopExecution::class);
        Buffer::clear();
        $cell = new FastExcelCell(555);
        $wrapper = new \Framelix\Framelix\Utils\FastExcelWrapper();
        $wrapper->setFromArray([
            ["C1", "C2", "C3"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abcöäü", 123, "=1+2"],
        ]);
        $sheet = $wrapper->excel->makeSheet();
        $wrapper->excel->setActiveSheet($sheet->sheetName);
        $wrapper->setFromArray([
            ["C1", "C2", "C3"],
            ["abc", 111, "=1+2"],
            ["abc", 222, "=1+2"],
            ["abc", 333, "=1+2"],
            ["abc", 444, "=1+2"],
            ["abcöäü", $cell, "=1+2"],
        ],
            sheet: $sheet);
        $tmpFile = FileUtils::getTmpFolder() . "/test.xlsx";
        $wrapper->save($tmpFile);
        Buffer::start();
        $this->assertExceptionOnCall(function () use ($tmpFile) {
            Response::download($tmpFile);
        }, [], StopExecution::class);
        Buffer::clear();

        $this->assertSame(
            '{"1":{"A":"C1","B":"C2","C":"C3"},"2":{"A":"abc","B":111,"C":"=1+2"},"3":{"A":"abc","B":222,"C":"=1+2"},"4":{"A":"abc","B":333,"C":"=1+2"},"5":{"A":"abc","B":444,"C":"=1+2"},"6":{"A":"abc\u00f6\u00e4\u00fc","B":555,"C":"=1+2"}}',
            json_encode(\Framelix\Framelix\Utils\FastExcelWrapper::readFileToArray($tmpFile, 2))
        );
    }

}
