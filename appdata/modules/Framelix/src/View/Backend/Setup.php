<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\RandomGenerator;
use Throwable;

use function file_exists;
use function file_put_contents;
use function implode;
use function sleep;
use function strtolower;

class Setup extends View
{
    protected string|bool $accessRole = "*";
    protected ?string $customUrl = "~.*~";
    protected bool $hiddenView = true;

    public function onRequest(): void
    {
        $userConfigFileCore = Config::getUserConfigFilePath();
        $userConfigFileUi = Config::getUserConfigFilePath("02-ui");
        $this->hideSidebarInitially = true;
        $this->layout = self::LAYOUT_SMALL_CENTERED;

        if (Form::isFormSubmitted('setup')) {
            $form = $this->getForm();
            $form->validate();
            if (Request::getPost('password') !== Request::getPost('password2')) {
                Response::showFormValidationErrorResponse(['password2' => '__framelix_password_notmatch__']);
            }
            try {
                $url = Url::create(strtolower(Request::getPost('applicationUrl')));

                Config::$applicationHost = $url->urlData['host'] . (($url->urlData['port'] ?? null) ? ":" . $url->urlData['port'] : '');
                Config::$applicationUrlPrefix = $url->urlData['path'];
                Config::$salts['default'] = RandomGenerator::getRandomString(64, 70);
                Mysql::get()->query(
                    "CREATE TABLE `__framelix_test__` (
                    `id` BIGINT(18) UNSIGNED NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (`id`) USING BTREE
                )"
                );
                Mysql::get()->query("DROP TABLE `__framelix_test__`");
                $builder = new MysqlStorableSchemeBuilder(Mysql::get());
                $queries = $builder->getQueries();
                foreach ($queries as $row) {
                    $builder->db->query($row['query']);
                }
                if (Request::getPost('email')) {
                    $user = User::getByConditionOne('email = {0}', [Request::getPost('email')]);
                    if (!$user) {
                        $user = new User();
                        $user->email = Request::getPost('email');
                        $user->roles = ['admin', 'dev'];
                    }
                    $user->flagLocked = false;
                    $user->addRole("admin");
                    $user->addRole("dev");
                    $user->setPassword(Request::getPost('password'));
                    $user->store();

                    $token = UserToken::create($user);
                    UserToken::setCookieValue($token->token);
                }


                Config::$applicationHost = $url->urlData['host'] . (($url->urlData['port'] ?? null) ? ":" . $url->urlData['port'] : '');
                Config::$applicationUrlPrefix = $url->urlData['path'];
                Config::$salts['default'] = RandomGenerator::getRandomString(64, 70);

                $db = Mysql::get();
                $fileContents = [
                    "<?php",
                    "\\Framelix\\Framelix\\Config::addSalt('" . Config::$salts["default"] . "');",
                    "\\Framelix\\Framelix\\Config::\$applicationHost = '" . Config::$applicationHost . "';",
                    "\\Framelix\\Framelix\\Config::\$applicationUrlPrefix = '" . Config::$applicationUrlPrefix . "';"
                ];
                file_put_contents($userConfigFileCore, implode("\n", $fileContents));

                $fileContents = [
                    "<?php",
                    "// this file will be modified with changes in backend UI for system config"
                ];
                file_put_contents($userConfigFileUi, implode("\n", $fileContents));

                // include now, so we can deal with errors in the catch handler, just in case
                require $userConfigFileCore;
                require $userConfigFileUi;

                // wait 3 seconds to let opcache refresh
                sleep(3);
            } catch (Throwable $e) {
                if (file_exists($userConfigFileCore)) {
                    unlink($userConfigFileCore);
                }
                if (file_exists($userConfigFileUi)) {
                    unlink($userConfigFileUi);
                }
                Response::showFormValidationErrorResponse($e->getMessage() . "\n" . $e->getTraceAsString());
            }
            Toast::success('__framelix_setup_done__');
            Url::getBrowserUrl()->redirect();
        }

        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton('setup', '__framelix_setup_finish_setup__', 'check');
        $form->show();
        ?>
        <script>
          (function () {
            // check if scripts have been loaded successfully, if not, we are on the wrong path
            if (typeof FramelixDeviceDetection === 'undefined') {
              const spl = window.location.pathname.split('/')
              spl.splice(1, 1)
              window.location.pathname = spl.join('/')
            }
          })()
        </script>
        <?php
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "setup";

        $field = new Text();
        $field->name = "applicationUrl";
        $field->label = "__framelix_setup_applicationurl_label__";
        $field->labelDescription = "__framelix_setup_applicationurl_desc__";
        $field->required = true;
        $field->type = "url";
        $field->defaultValue = Url::getApplicationUrl()->getUrlAsString();
        $form->addField($field);

        if (!User::getByCondition()) {
            $field = new Html();
            $field->name = "headerSecurity";
            $field->defaultValue = '<h2>' . Lang::get('__framelix_setup_step_security_desc__') . '</h2>';
            $form->addField($field);

            $field = new Email();
            $field->name = "email";
            $field->label = "__framelix_email__";
            $field->required = true;
            $field->maxWidth = null;
            $form->addField($field);

            $field = new Password();
            $field->name = "password";
            $field->label = "__framelix_password__";
            $field->minLength = 8;
            $form->addField($field);

            $field = new Password();
            $field->name = "password2";
            $field->label = "__framelix_password_repeat__";
            $field->minLength = 8;
            $form->addField($field);
        }

        return $form;
    }
}