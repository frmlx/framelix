<?php

namespace Network;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

final class SessionTest extends TestCase
{

    public function tests(): void
    {
        Session::$sessionName = "tests";
        // delete old test files if exist
        $allSessionsDir = FileUtils::getUserdataFilepath("sessions", false, autoCreateFolder: false);
        if (is_dir($allSessionsDir)) {
            $files = FileUtils::getFiles($allSessionsDir, "~/tests_[^/]+\.json$~i", true);
            FileUtils::deleteFiles($files, true);
        }
        $this->assertNull(Session::get('foo'));
        Session::set('foo', '123456');
        $this->assertSame('123456', Session::get('foo'));
        Session::set('foo', '123456');
        $this->assertSame('123456', Session::get('foo'));
        $this->assertCount(1, Session::getAll());
        Session::set('foo', null);
        $this->assertNull(Session::get('foo'));
        Session::set('foo2', 1);
        $this->assertSame(1, Session::get('foo2'));


        $files = FileUtils::getFiles($allSessionsDir, "~/tests_[^/]+\.json$~i", true);
        $this->assertCount(1, $files);

        // test clearing
        Session::clear();
        $this->assertCount(0, Session::getAll());
        $this->assertFileDoesNotExist(Session::getSessionFilePath());

        // test cleanup
        Session::cleanup(DateTime::create("now + 10 days"));
        $files = FileUtils::getFiles($allSessionsDir, "~/tests_[^/]+\.json$~i", true);
        $this->assertCount(0, $files);
    }

}
