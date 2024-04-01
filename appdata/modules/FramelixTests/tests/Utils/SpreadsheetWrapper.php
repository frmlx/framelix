<?php

namespace Framelix\FramelixTests\tests\Utils;

use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Utils\Buffer;
use Framelix\FramelixTests\TestCase;

final class SpreadsheetWrapper extends TestCase
{

    public function tests(): void
    {
        $wrapper = \Framelix\Framelix\Utils\SpreadsheetWrapper::create();
        $wrapper->setFromArray([
            ["C1", "C2", "C3"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abc", 123, "=1+2"],
            ["abcöäü", 123, "=1+2"],
        ], null, true, "A1:*1");
        Buffer::start();
        $this->assertExceptionOnCall(function () use ($wrapper) {
            $wrapper->download("test.xlsx");
        }, [], StopExecution::class);
        Buffer::clear();

        Buffer::start();
        $this->assertExceptionOnCall(function () use ($wrapper) {
            $wrapper->download("test.csv");
        }, [], StopExecution::class);
        Buffer::clear();

        Buffer::start();
        $this->assertExceptionOnCall(function () use ($wrapper) {
            $wrapper->download("test.csv", true);
        }, [], StopExecution::class);
        Buffer::clear();


        $wrapper = \Framelix\Framelix\Utils\SpreadsheetWrapper::create();
        $wrapper->setFromArrayMultiple([
            "sheet1" => [
                ["C1", "C2", "C3"],
                ["abc", 123, "=1+2"],
                ["abc", 123, "=1+2"],
                ["abc", 123, "=1+2"],
                ["abc", 123, "=1+2"],
                ["abcöäü", 123, "=1+2"],
            ],
            "sheet2" => [
                ["C1", "C2", "C3"],
                ["abc", 123, "=1+2"],
                ["abc", 123, "=1+2"],
                ["abc", 123, "=1+2"],
                ["abc", 123, "=1+2"],
                ["abcöäü", 123, "=1+2"],
            ],
        ], true, "A1:*1");
        Buffer::start();
        $this->assertExceptionOnCall(function () use ($wrapper) {
            $wrapper->download("test.xlsx");
        }, [], StopExecution::class);
        Buffer::clear();

        Buffer::start();
        $this->assertExceptionOnCall(function () use ($wrapper) {
            $wrapper->download("test.csv");
        }, [], StopExecution::class);
        Buffer::clear();

        Buffer::start();
        $this->assertExceptionOnCall(function () use ($wrapper) {
            $wrapper->download("test.csv", true);
        }, [], StopExecution::class);
        Buffer::clear();

        $this->assertSame('[["C1","C2","C3"],["abc","123","3"],["abc","123","3"],["abc","123","3"],["abc","123","3"],["abc\u00f6\u00e4\u00fc","123","3"]]', json_encode($wrapper->toArray(0)));
    }

}
