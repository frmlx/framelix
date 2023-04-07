<?php

namespace Db\Storables;

use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserVerificationToken;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class UserVerificationTokenTestBase extends TestCaseDbTypes
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

        $token = UserVerificationToken::create($user, UserVerificationToken::CATEGORY_FORGOT_PASSWORD);
        $this->assertSame($token, UserVerificationToken::getForToken($token->token));
        $this->assertNull(UserVerificationToken::getForToken($token->token . "s"));
        $this->assertNull(UserVerificationToken::getForToken(null));
        $this->assertStorableDefaultGetters($token);
        $token->delete();
    }
}