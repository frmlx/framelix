<?php

namespace Db\Storables;

use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserWebAuthn;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class UserWebAuthnTestBase extends TestCaseDbTypes
{
    public function test(): void
    {
        $this->setupDatabase(true);

        $user = new User();
        $user->email = "test@test";
        $user->flagLocked = false;
        $user->setPassword("blub");
        $user->store();
        $user->addRole("foo");
        $user->addRole("bar");
        $this->assertSame(1, $user->id);

        $webAuthn = new UserWebAuthn();
        $webAuthn->user = $user;
        $webAuthn->deviceName = "foo";
        $webAuthn->store();
        $this->assertStorableDefaultGetters($webAuthn);
        $webAuthn->delete();
    }
}