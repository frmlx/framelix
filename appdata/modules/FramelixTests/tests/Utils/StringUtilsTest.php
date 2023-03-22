<?php

namespace Utils;

use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\StringUtils;
use Framelix\FramelixTests\TestCase;

final class StringUtilsTest extends TestCase
{

    public function tests(): void
    {
        $user = new User();
        $user->email = "test@test.de";
        $objTest = new HtmlAttributes();
        $objTest->set('data', 'test');
        $this->assertSame('test@test.de', StringUtils::stringify($user));
        $this->assertSame('foo, bar', StringUtils::stringify(['foo', 'bar']));
        $this->assertSame('1,33', StringUtils::stringify(1.33));
        $this->assertSame('1', StringUtils::stringify(1.0));
        $this->assertSame('', StringUtils::stringify([]));
        $this->assertSame('foo', StringUtils::stringify("foo"));
        $this->assertSame('data="test"', StringUtils::stringify($objTest));
        $this->assertSame(Lang::get('__framelix_yes__'), StringUtils::stringify(true));
        $this->assertSame('some-pretty-string', StringUtils::slugify("some pretty %] string"));
        $this->assertSame('some prett...', StringUtils::cut("some pretty string", 10, "...", false));
        $this->assertSame('some...', StringUtils::cut("some pretty string", 10, "...", true));
        $this->assertSame('some pretty string', StringUtils::cut("some pretty string", 100, "...", true));
    }
}
