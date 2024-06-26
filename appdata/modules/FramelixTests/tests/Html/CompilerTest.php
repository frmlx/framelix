<?php

namespace Html;

use Framelix\Framelix\Config;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

use PHPUnit\Framework\Attributes\Depends;

use function file_get_contents;
use function file_put_contents;
use function unlink;

use const FRAMELIX_MODULE;

final class CompilerTest extends TestCase
{

    public function testCleanup(): void
    {
        // remove previous dist files
        $files = FileUtils::getFiles(__DIR__ . "/../../public/dist", "~\.(css|js|json)$~i", true);
        foreach ($files as $file) {
            unlink($file);
        }
        $files = FileUtils::getFiles(__DIR__ . "/../../public/dist", "~\.(css|js|json)$~i", true);
        $this->assertCount(0, $files);
    }

    #[Depends("testCleanup")]
    public function testBabel(): void
    {
        Config::$devMode = false;
        // when not in dev mode, calling a compiler if not available does nothing
        $this->assertNull(Compiler::compile(FRAMELIX_MODULE));
        // in dev mode, compiling is active
        Config::$devMode = true;
    }

    #[Depends("testBabel")]
    public function tests(): void
    {
        $distFolder = __DIR__ . "/../../public/dist";
        $noCompiledFile = $distFolder . "/js/test-nocompile.min.js";
        Config::$devMode = true;
        Compiler::$cache = [];
        $this->assertCount(6, Compiler::compile(FRAMELIX_MODULE, true));
        // already cached
        $this->assertNull(Compiler::compile(FRAMELIX_MODULE));
        // reset cache, should still not update but do filechecks
        Compiler::$cache = [];
        $this->assertCount(0, Compiler::compile(FRAMELIX_MODULE));
        Compiler::$cache = [];
        $jsFile = __DIR__ . "/../../js/framelix-unit-test-jstest.js";
        // compiling invalid module does nothing
        $this->assertNull(Compiler::compile("FOO"));
        // injecting a dist file that not exist in config, will trigger delete of this file
        Compiler::$cache = [];
        file_put_contents($distFolder . "/css/fakefile.css", '');
        $distFiles = FileUtils::getFiles($distFolder, null, true);
        Compiler::compile(FRAMELIX_MODULE);
        $this->assertCount(count($distFiles) - 1, FileUtils::getFiles($distFolder, null, true));

        $this->assertSame(
          ";" . trim(file_get_contents($jsFile)) . ";",
          trim(file_get_contents($noCompiledFile)),
        );
    }

    #[Depends("tests")]
    public function testUrls(): void
    {
        $this->assertInstanceOf(
          Url::class,
          Config::getCompilerFileBundle(FRAMELIX_MODULE, "js", "test-path")->getGeneratedBundleUrl()
        );
        $this->assertNull(Config::getCompilerFileBundle(FRAMELIX_MODULE, "js", "test-paths"));
    }

}
