<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\TwoFactorCode;
use Framelix\Framelix\Network\Session;
use Framelix\FramelixTests\TestCase;
use RobThree\Auth\TwoFactorAuth;

final class TwoFactorCodeTest extends TestCase
{
    public function tests(): void
    {
        $tfa = new TwoFactorAuth();
        $secret = $tfa->createSecret();

        $field = new TwoFactorCode();
        $field->name = $field::class;
        $this->callFormFieldDefaultMethods($field);
        Session::set(TwoFactorCode::SESSIONNAME_SECRET, $secret);
        Session::set(TwoFactorCode::SESSIONNAME_BACKUPCODES, ['ABCDEFGHIJ']);

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
