<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\TwoFactorCode;
use Framelix\Framelix\Network\Cookie;
use Framelix\FramelixTests\TestCase;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;

final class TwoFactorCodeTest extends TestCase
{
    public function tests(): void
    {
        $tfa = new TwoFactorAuth(new QRServerProvider());
        $secret = $tfa->createSecret();

        $field = new TwoFactorCode();
        $field->name = $field::class;
        $this->callFormFieldDefaultMethods($field);
        Cookie::set(TwoFactorCode::COOKIE_NAME_SECRET, $secret, encrypted: true);
        Cookie::set(TwoFactorCode::COOKIE_NAME_BACKUPCODES, ['ABCDEFGHIJ'], encrypted: true);

        $this->assertIsString($field->validate());

        // update name to prevent caching of converted submitted values
        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => $tfa->getCode($secret)]);
        $this->assertTrue($field->validate());

        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => "ABCDEFGHIJ"]);
        $this->assertTrue($field->validate());
    }
}
