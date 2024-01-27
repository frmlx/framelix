<?php

namespace Utils;

use Framelix\Framelix\Config;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\FramelixTests\TestCase;

final class HtmlUtilsTest extends TestCase
{

    public function tests(): void
    {
        $this->assertSame('&amp;', HtmlUtils::escape('&'));
        $this->assertSame('&', HtmlUtils::unescape('&amp;'));
        $this->assertSame("&amp;<br />\n", HtmlUtils::escape("&\n", true));
        $this->assertIsString(HtmlUtils::getIncludeTagForUrl(Url::create()->appendPath(".css")));
        $this->assertIsString(HtmlUtils::getIncludeTagForUrl(Url::create()->appendPath(".js")));
        $this->assertIsString(HtmlUtils::getIncludeTagsForBundles(Config::$compilerFileBundles));

        $this->assertExceptionOnCall(function () {
            HtmlUtils::getIncludeTagForUrl(Url::create()->appendPath(".jpeg"));
        });
    }
}
