<?php

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Form;
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

        $this->assertSame(
            FRAMELIX_USERDATA_FOLDER . '/FramelixTests/private/config/03-custom.php',
            Config::getUserConfigFilePath('03-custom')
        );

        $this->assertInstanceOf(
            Form::class,
            Config::getEditableConfigForm()
        );
    }
}
