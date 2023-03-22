<?php

namespace Utils;

use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\FramelixTests\TestCase;

use function base64_encode;
use function file_exists;
use function unlink;

final class JsonUtilsTest extends TestCase
{

    public function tests(): void
    {
        $tmpFile = __DIR__ . "/../../tmp/test-json.json";
        $this->assertSame('"blab\u00f6\u00e4\u00fc\u00df"', JsonUtils::encode('blaböäüß'));
        // using base64 because it contains white space and new lines which are hard to keep on code format
        $this->assertSame(
            'WwogICAgImJsYWJcdTAwZjZcdTAwZTRcdTAwZmNcdTAwZGYiCl0=',
            base64_encode(JsonUtils::encode(['blaböäüß'], true))
        );
        $this->assertSame('blaböäüß', JsonUtils::decode('"blab\u00f6\u00e4\u00fc\u00df"'));
        JsonUtils::writeToFile($tmpFile, 'blaböäüß');
        $this->assertSame('blaböäüß', JsonUtils::readFromFile($tmpFile));
        Buffer::start();
        JsonUtils::output('blaböäüß');
        $this->assertSame('"blab\u00f6\u00e4\u00fc\u00df"', Buffer::get());
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }

        $this->assertSame('framelix', JsonUtils::getPackageJson("Framelix")['name']);
        $this->assertSame('framelix', JsonUtils::getPackageJson("Framelix")['name']);
        $this->assertSame(null, JsonUtils::getPackageJson(null));
    }
}
