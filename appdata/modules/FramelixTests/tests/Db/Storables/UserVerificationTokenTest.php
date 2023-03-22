<?php

namespace Db\Storables;

use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserVerificationToken;
use Framelix\FramelixTests\TestCase;

final class UserVerificationTokenTest extends TestCase
{
    public function test(): void
    {
        $this->setupDatabase(true);

        $user = new User();
        $user->email = "test@test";
        $user->flagLocked = false;
        $user->setPassword("blub");
        $user->addRole("foo");
        $user->addRole("bar");
        $user->store();
        $this->assertSame(1, $user->id);

        $token = UserVerificationToken::create($user, UserVerificationToken::CATEGORY_FORGOT_PASSWORD);
        $this->assertSame($token, UserVerificationToken::getForToken($token->token));
        $this->assertNull(UserVerificationToken::getForToken($token->token . "s"));
        $this->assertNull(UserVerificationToken::getForToken(null));
        $this->assertStorableDefaultGetters($token);
        $token->delete();
    }
}