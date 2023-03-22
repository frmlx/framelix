<?php

namespace Html;

use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Url;
use Framelix\FramelixTests\TestCase;
use Framelix\FramelixTests\View\TestBackendView;
use Framelix\FramelixTests\View\TestBackendViewAccessRole;

final class TabsTest extends TestCase
{

    public function tests(): void
    {
        $object = new Tabs();
        $this->callMethodsGeneric($object);

        $object = new Tabs();
        $object->addTab('test', null, new TestBackendView());
        $object->addTab('test2', null, new TestBackendViewAccessRole());
        $object->addTab('test3', null, Url::create());
        json_encode($object);
    }
}
