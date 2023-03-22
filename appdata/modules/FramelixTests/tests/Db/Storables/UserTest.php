<?php

namespace Db\Storables;

use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\FramelixTests\TestCase;

final class UserTest extends TestCase
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

        $user2 = new User();
        $user2->email = "test@test2";
        $user2->flagLocked = false;
        $user2->setPassword("blub");
        $user2->store();

        $user3 = new User();
        $user3->email = "test@test3";
        $user3->flagLocked = true;
        $user3->setPassword("blub");
        $user3->store();

        $token = UserToken::create($user);
        $this->assertTrue($token->isDeletable());
        UserToken::setCookieValue($token->token);

        // test tokens and if token lead to correct user
        $this->assertSame($token, UserToken::getByCookie());
        $this->assertSame($token, UserToken::getByCookie());
        $this->assertSame($user, User::get());

        // test user override
        User::setCurrentUser($user2);
        $this->assertSame($user2, User::get());
        User::setCurrentUser(null);
        $this->assertSame($user, User::get());

        // pw check
        $this->assertTrue($user->passwordVerify("blub"));
        $this->assertFalse($user->passwordVerify("Blub"));

        // role checks
        $this->assertFalse(User::hasRole('blab'));
        $this->assertFalse(User::hasRole('blab', $user));
        $this->assertFalse(User::hasRole('blab', null));
        $this->assertTrue(User::hasRole(',foo'));
        $this->assertTrue(User::hasRole(true));
        $this->assertFalse(User::hasRole(false));
        $this->assertFalse(User::hasRole(true, null));
        $this->assertTrue(User::hasRole(false, null));
        $this->assertTrue(User::hasRole("*"));
        $user->removeRole('foo');
        $this->assertFalse(User::hasRole(',foo'));
        $this->assertTrue(User::hasRole('bar'));

        // some getters
        $this->assertSame($user, User::getByEmail("test@test"));
        $this->assertSame($user3, User::getByEmail("test@test3", true));
        $this->assertNull(User::getByEmail("test@test3"));

        $this->assertStorableDefaultGetters($user);
        $this->assertStorableDefaultGetters($token);
    }
}