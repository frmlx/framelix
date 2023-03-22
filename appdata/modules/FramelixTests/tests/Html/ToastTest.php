<?php

namespace Html;

use Framelix\Framelix\Html\Toast;
use Framelix\FramelixTests\TestCase;

final class ToastTest extends TestCase
{

    public function tests(): void
    {
        $this->assertFalse(Toast::hasInfo());
        $this->assertFalse(Toast::hasSuccess());
        $this->assertFalse(Toast::hasWarning());
        $this->assertFalse(Toast::hasError());
        Toast::info('foo');
        $this->assertTrue(Toast::hasInfo());
        Toast::success('foo');
        $this->assertTrue(Toast::hasSuccess());
        Toast::warning('foo');
        $this->assertTrue(Toast::hasWarning());
        Toast::error('foo');
        $this->assertTrue(Toast::hasError());
        $this->assertCount(4, Toast::getQueueMessages(true));
        $this->assertCount(0, Toast::getQueueMessages(true));
    }
}
