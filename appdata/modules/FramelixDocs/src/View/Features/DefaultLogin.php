<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\View\Backend\Login;
use Framelix\FramelixDocs\View\View;

class DefaultLogin extends View
{
    protected string $pageTitle = 'Default Login and User features';

    public function showContent(): void
    {
        $this->showDataResetTimer();
        ?>
        <p>
            Framelix comes with a default layout and backend, as you probably already have learned.
            With this default backend comes basic features such as Login, Authentication, 2-Factor Features (inluding
            Web-Authn), User Management, Development Features, etc...
        </p>
        <p>
            To see all this features, you must be logged in.
            The docs provide some demo users with full access to all features (Admin role), live and interactive.
            You can try it all out.
            All data is reset automatically each hour, then new users will be created with new passwords.
        </p>
        <p>
            To login, choose a user from the list bellow and goto the login page (Link at the end).
        </p>
        <blockquote>
            After you have logged in, you will see new available links in the sidebar at the very bottom. For the protected user area.
        </blockquote>
        <?php
        $users = User::getByCondition();
        $table = new Table();
        $table->createHeader(['email' => 'E-Mail', 'password' => 'Password']);
        foreach ($users as $user) {
            if (!($user->settings['pwRaw'] ?? null)) {
                continue;
            }
            $table->createRow(['email' => $user->email, 'password' => $user->settings['pwRaw']]);
        }
        $table->show();
        ?>
        <framelix-button href="<?= \Framelix\Framelix\View::getUrl(Login::class) ?>" block icon="70b" theme="primary">
            Login now
        </framelix-button>
        <?php
    }
}