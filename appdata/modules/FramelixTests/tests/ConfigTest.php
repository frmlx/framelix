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
            '/framelix/userdata/FramelixTests/private/config/test.php',
            Config::getUserConfigFilePath('test')
        );

        $this->assertInstanceOf(
            Form::class,
            Config::getEditableConfigForm()
        );
    }
}
