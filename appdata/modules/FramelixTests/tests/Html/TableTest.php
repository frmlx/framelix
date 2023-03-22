<?php

namespace Html;

use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Time;
use Framelix\Framelix\Utils\Buffer;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\TestCase;

use function json_encode;

final class TableTest extends TestCase
{

    public function tests(): void
    {
        $this->setupDatabase();
        $testStorable = TestStorable2::getNewTestInstance();
        $testStorable2 = TestStorable2::getNewTestInstance();
        $object = new Table();
        $this->assertInstanceOf(HtmlAttributes::class, $object->getCellHtmlAttributes(1, 'test'));

        $this->callMethodsGeneric($object);

        $jsCall = new JsCall(
            "storableSort",
            [
                'data' => [
                    [$testStorable2->id, $testStorable2->getDb()->id],
                    [$testStorable->id, $testStorable->getDb()->id]
                ]
            ]
        );
        Table::onJsCall($jsCall);
        $this->assertSame(0, $testStorable2->sort);
        $this->assertSame(1, $testStorable->sort);

        $table = new Table();
        $table->createHeader(['test' => 'foo']);
        $table->createRow(['test' => 1]);
        $table->createRow(['test' => "1,22"]);
        $table->createRow(['test' => Time::create('01:00')]);
        $table->footerSumColumns = ['test'];
        Buffer::start();
        $table->show();
        Buffer::clear();

        $this->assertExceptionOnCall(function () {
            $table = new Table();
            $table->footerSumColumns = ['foo'];
            json_encode($table);
        });
    }
}
