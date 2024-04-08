<?php

namespace Framelix\FramelixDemo\View;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Html\TypeDefs\JsRequestOptions;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Mutex;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View\Backend\View;
use Framelix\FramelixDemo\Cron;

class Index extends View
{

    protected string|bool $accessRole = "*";

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'resetpw') {
            $user = User::getByEmail('admin@test.local', true);
            if ($user) {
                $pw = RandomGenerator::getRandomString(5, 10);
                $user->setPassword($pw);
                $user->settings = ['pwRaw' => $pw];
                $user->store();
            }
            Toast::success('A new password has been generated');
            Url::getBrowserUrl()->redirect();
        }
    }

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $lifetimeRemains = Mutex::isLocked(Cron::CLEANUP_MUTEX_NAME, Cron::CLEANUP_MUTEX_LIFETIME);
        if ($lifetimeRemains > 0) {
            echo '<framelix-alert theme="warning">This app does reset all data every hour. Next data reset at  ' . DateTime::create(
                    'now + ' . $lifetimeRemains . ' seconds'
                )->getHtmlString() . '</framelix-alert>';
        }
        ?>
      <h1>Welcome to the Framelix Demo Application</h1>
      <p>
        This application as an example of what you can make with Framelix.
        The application is a copy of one our internal accounting software products that are build with Framelix.
      </p>
      <h2>The main features are</h2>
      <ul>
        <li>Manage incomes and outgoings for your company</li>
        <li>Manage and create PDF invoices and offers</li>
        <li>Excel reporting and exports</li>
        <li>Quick search features to find entries</li>
        <li>Multilanguage interface (English and German only for this demo). Choose at the top right user settings
          icon.
        </li>
        <li>With the demo user, you are an administrator and see all features, including user management, logs and
          stuff that a normal user probably never need to see.
        </li>
      </ul>
      <h2>Prefilled with demo data</h2>
      <p>
        The application have a lot of demo entries to show you an interface with data, even if the data itself make
        absolutely no sense.
      </p>
        <?php
        if (!User::get()) {
            ?>
          <h2>Login</h2>
          <p>
          On the left, you have a login link.<br/>
          Admin user credentials are:<br/><br/>
            <?php
            $user = User::getByEmail('admin@test.local', true);
            if (!$user) {
                // create the admin user if not yet exist
                $user = new  User();
                $user->email = "admin@test.local";
                $pw = RandomGenerator::getRandomString(5, 10);
                $user->setPassword($pw);
                $user->settings = ['pwRaw' => $pw];
                $user->flagLocked = false;
                $user->store();
                $user->addRole('admin');
            }
            echo 'E-Mail: <code>' . $user->email . '</code><br/>';
            echo 'Password: <code>' . $user->settings['pwRaw'] . '</code><br/>';
            ?>
          <br/>
          If you can't login, someone probably have changed the password. Click the PW reset button bellow, to
          generate a
          new password.
          <br/>
          <br/>
          <framelix-button request-options='<?= new JsRequestOptions(JsCall::getUrl(__CLASS__, 'resetpw')) ?>'
                           theme="primary"
                           icon="785">Reset password
          </framelix-button>
            <?php
        }
        ?>
      </p>
        <?php
    }

}