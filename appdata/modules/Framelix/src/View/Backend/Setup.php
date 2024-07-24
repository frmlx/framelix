<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Console;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Db\SqlStorableSchemeBuilder;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use mysqli;
use Throwable;

use function file_exists;
use function file_put_contents;
use function sleep;
use function strtolower;

use const FILE_APPEND;
use const FRAMELIX_MODULE;

class Setup extends View
{

    protected string|bool $accessRole = "*";

    protected ?string $customUrl = "~.*~";

    protected bool $hiddenView = true;

    public function onRequest(): void
    {
        $userConfigFile = Config::getUserConfigFilePath();
        $this->sidebarClosedInitially = true;
        $this->layout = self::LAYOUT_SMALL_CENTERED;

        if (Form::isFormSubmitted('setup')) {
            $form = $this->getForm();
            $form->validate();
            if (Request::getPost('password') !== Request::getPost('password2')) {
                Response::stopWithFormValidationResponse(['password2' => '__framelix_password_notmatch__']);
            }
            try {
                $configLine = null;
                if (!isset(Config::$sqlConnections[FRAMELIX_MODULE])) {
                    if (Request::getPost('mysql')) {
                        $databaseName = Request::getPost('mysql_database');
                        $databaseHost = Request::getPost('mysql_host');
                        $databaseUser = Request::getPost('mysql_username');
                        $databasePw = Request::getPost('mysql_password');
                        $databasePort = (int)Request::getPost('mysql_port');
                        $configLine = '\Framelix\Framelix\Config::addMysqlConnection(FRAMELIX_MODULE, "' . $databaseName . '", "' . $databaseHost . '", "' . $databaseUser . '", "' . $databasePw . '", ' . $databasePort . ');';
                        // create database if not yet exists
                        $mysqli = new mysqli(
                            $databaseHost,
                            $databaseUser,
                            $databasePw,
                            null,
                            $databasePort
                        );
                        $mysqli->query('CREATE DATABASE IF NOT EXISTS `' . $databaseName . '`');
                        $mysqli->close();
                        Config::addMysqlConnection(
                            FRAMELIX_MODULE,
                            $databaseName,
                            $databaseHost,
                            $databaseUser,
                            $databasePw,
                            $databasePort,
                        );
                    } else {
                        $configLine = '\Framelix\Framelix\Config::addSqliteConnection(FRAMELIX_MODULE, "' . Request::getPost(
                                'sqlite_path'
                            ) . '");';
                        Config::addSqliteConnection(FRAMELIX_MODULE, Request::getPost('sqlite_path'));
                    }
                }
                $db = Sql::get();

                $url = Url::create(strtolower(Request::getPost('applicationUrl')));
                Config::$applicationHost = $url->urlData['host'] . (($url->urlData['port'] ?? null) ? ":" . $url->urlData['port'] : '');
                Config::$applicationUrlPrefix = $url->urlData['path'];
                Config::$salts['default'] = RandomGenerator::getRandomString(64, 70);

                $builder = new SqlStorableSchemeBuilder($db);
                $queries = $builder->getSafeQueries();
                $builder->executeQueries($queries);

                $user = User::getByConditionOne('email = {0}', [Request::getPost('email')]);
                if (!$user) {
                    $user = new User();
                    $user->email = Request::getPost('email');
                }
                $user->flagLocked = false;
                $user->setPassword(Request::getPost('password'));
                $user->store();
                $user->addRole("admin");
                $user->addRole("system");
                if (Config::$devMode) {
                    $user->addRole("dev");
                }

                Config::$applicationHost = $url->urlData['host'] . (($url->urlData['port'] ?? null) ? ":" . $url->urlData['port'] : '');
                Config::$applicationUrlPrefix = $url->urlData['path'];
                Config::$salts['default'] = RandomGenerator::getRandomString(64, 70);

                Config::createInitialUserConfig(
                    FRAMELIX_MODULE,
                    Config::$salts["default"],
                    Config::$applicationHost,
                    Config::$applicationUrlPrefix
                );

                if ($configLine) {
                    // append database config to the config file
                    file_put_contents($userConfigFile, "\n$configLine\n", FILE_APPEND);
                }

                // include now, so we can deal with errors in the catch handler, just in case
                require $userConfigFile;

                // wait 3 seconds to let opcache refresh
                sleep(3);

                // run first time warmup with a fully setup app
                Console::callMethodInSeparateProcess('appWarmup');
            } catch (Throwable $e) {
                if (file_exists($userConfigFile)) {
                    unlink($userConfigFile);
                }
                Response::stopWithFormValidationResponse($e->getMessage() . "\n" . $e->getTraceAsString());
            }
            Toast::success('__framelix_setup_done__');
            Url::getBrowserUrl()->redirect();
        }

        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton('setup', '__framelix_setup_finish_setup__');
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

        if (!isset(Config::$sqlConnections[FRAMELIX_MODULE])) {
            $field = new Toggle();
            $field->name = "mysql";
            $field->label = "__framelix_setup_mysql_label__";
            $field->labelDescription = "__framelix_setup_mysql_desc__";
            $form->addField($field);

            $field = new Text();
            $field->getVisibilityCondition()->empty('mysql');
            $field->name = "sqlite_path";
            $field->required = true;
            $field->label = "__framelix_setup_sqlite_path__";
            $field->defaultValue = FileUtils::getUserdataFilepath(FRAMELIX_MODULE . ".db", false);
            $form->addField($field);

            $field = new Text();
            $field->getVisibilityCondition()->equal('mysql', 1);
            $field->name = "mysql_host";
            $field->required = true;
            $field->label = "__framelix_setup_mysql_host__";
            $field->labelDescription = "__framelix_setup_mysql_host_desc__";
            $field->defaultValue = "mariadb";
            $form->addField($field);

            $field = new Text();
            $field->getVisibilityCondition()->equal('mysql', 1);
            $field->name = "mysql_port";
            $field->required = true;
            $field->label = "__framelix_setup_mysql_port__";
            $field->defaultValue = "3306";
            $form->addField($field);

            $field = new Text();
            $field->getVisibilityCondition()->equal('mysql', 1);
            $field->name = "mysql_username";
            $field->required = true;
            $field->label = "__framelix_setup_mysql_username__";
            $field->defaultValue = "app";
            $form->addField($field);

            $field = new Password();
            $field->getVisibilityCondition()->equal('mysql', 1);
            $field->name = "mysql_password";
            $field->required = true;
            $field->label = "__framelix_setup_mysql_password__";
            $form->addField($field);

            $field = new Text();
            $field->getVisibilityCondition()->equal('mysql', 1);
            $field->name = "mysql_database";
            $field->required = true;
            $field->label = "__framelix_setup_mysql_database__";
            $field->labelDescription = "__framelix_setup_mysql_database_desc__";
            $field->defaultValue = FRAMELIX_MODULE;
            $form->addField($field);
        }

        $field = new Html();
        $field->name = "headerSecurity";
        $field->defaultValue = '<h2>' . Lang::get('__framelix_setup_step_security_title__') . '</h2><div>' . Lang::get(
                '__framelix_setup_step_security_desc__'
            ) . '</div>';
        $form->addField($field);

        $field = new Email();
        $field->name = "email";
        $field->label = "__framelix_email__";
        $field->required = true;
        $field->maxWidth = null;
        $form->addField($field);

        $field = new Password();
        $field->name = "password";
        $field->required = true;
        $field->label = "__framelix_password__";
        $field->minLength = 8;
        $form->addField($field);

        $field = new Password();
        $field->name = "password2";
        $field->required = true;
        $field->label = "__framelix_password_repeat__";
        $field->minLength = 8;
        $form->addField($field);

        return $form;
    }

}