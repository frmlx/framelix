<?php

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Framelix;
use Framelix\FramelixTests\TestCase;

final class ConfigTest extends TestCase
{
    public function tests(): void
    {
        // just re-register existing modules and they use all config features
        $originalSalt = Config::$salts['default'];
        Framelix::$registeredModules = [];

        Framelix::registerModule("Framelix");
        Framelix::registerModule("FramelixTests");

        $this->assertSame($originalSalt, Config::$salts['default']);

        $configFile = FRAMELIX_USERDATA_FOLDER . '/FramelixTests/private/config/01-app.php';
        $this->assertSame(
            $configFile,
            Config::getUserConfigFilePath()
        );

        $originalData = file_get_contents($configFile);
        unlink($configFile);
        Config::createInitialUserConfig(FRAMELIX_MODULE, 'test', 'test', 'test');

        $this->assertFileExists($configFile);
        file_put_contents($configFile, $originalData);

        Config::setTimeAndMemoryLimit(4);
        Config::addCaptchaKey(Captcha::TYPE_RECAPTCHA_V2, 'test', 'test');
        Config::addMysqlConnection('test', 'test', 'test');
        Config::addPostgresConnection('test', 'test', 'test');
        Config::addSqliteConnection('test', 'test');
        Config::getCompilerFileBundle('test', 'scss', 'test');
    }
}
