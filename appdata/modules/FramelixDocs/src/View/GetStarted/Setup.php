<?php

namespace Framelix\FramelixDocs\View\GetStarted;

use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\FramelixDocs\View\View;

use function array_filter;
use function explode;
use function file_get_contents;
use function implode;
use function is_array;
use function preg_replace;
use function str_replace;
use function substr;

class Setup extends View
{
    protected string $pageTitle = 'Setup for module development';

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'create-config') {
            if (Form::isFormSubmitted('config')) {
                $view = new self();
                $contents = file_get_contents(__DIR__ . "/../../../misc/docker-compose-starter.yml");
                $vars = Request::getPost('var');
                $vars['includeDocs'] = (int)NumberUtils::toFloat($vars['includeDocs'] ?? 0, 0);
                if (is_array($vars)) {
                    $vars['volumename'] = $vars['projectname'] . "_db";
                    foreach ($vars as $key => $value) {
                        $contents = str_replace('${' . $key . '}', $value, $contents);
                    }
                }
                if (!Request::getPost('devmode')) {
                    $contents = str_replace("- FRAMELIX_DEVMODE=1", '', $contents);
                }
                if (!Request::getPost('mysql')) {
                    $contents = preg_replace("~# mariadb-start.*?# mariadb-end~s", '', $contents);
                }
                if (!Request::getPost('backup') || !Request::getPost('mysql')) {
                    $contents = preg_replace("~# mariadb-backup-start.*?# mariadb-backup-end~s", '', $contents);
                }
                if ($vars['includeDocs']) {
                    $contents = preg_replace("~# modules-default .*~m", '', $contents);
                    $contents = str_replace(['# modules-includeDocs'], '', $contents);
                } else {
                    $contents = preg_replace("~# modules-includeDocs .*~m", '', $contents);
                    $contents = str_replace(['# modules-default'], '', $contents);
                }
                if (Request::getPost('framelixmount')) {
                    $contents = preg_replace("~^#( .*?appdata/modules/Framelix.*$)~m", '$1', $contents);
                }
                $contents = str_replace([
                    '# mariadb-start',
                    '# mariadb-end',
                    '# mariadb-backup-start',
                    '# mariadb-backup-end'
                ], '', $contents);
                $lines = explode("\n", $contents);
                $contents = implode(
                    "\n",
                    array_filter($lines, function ($line) {
                        if (!trim($line)) {
                            return false;
                        }
                        return true;
                    })
                );
                $view->showCodeBlock($contents, 'yml', 'docker-compose.yml');
                Response::stopWithFormValidationResponse();
            }
            ?>
            <framelix-alert>
                This tool will create you a docker-compose.yml which you can copy to your empty folder.
            </framelix-alert>
            <?php
            $form = new Form();
            $form->id = 'config';
            $form->submitUrl = Url::create();

            $field = new Text();
            $field->required = true;
            $field->name = "var[projectname]";
            $field->label = "Project Name (Lowercase, no whitespace)";
            $field->defaultValue = "framelix_starter";
            $form->addField($field);

            $field = new Toggle();
            $field->name = "mysql";
            $field->label = "Use MariaDb/Mysql";
            $field->labelDescription = 'Instead of Sqlite';
            $form->addField($field);

            $field = new Text();
            $field->required = true;
            $field->getVisibilityCondition()->equal('mysql', '1');
            $field->name = "var[mysqlpw]";
            $field->label = "Database 'root' user password";
            $field->defaultValue = RandomGenerator::getRandomString(10, 20);
            $form->addField($field);

            $field = new Text();
            $field->required = true;
            $field->getVisibilityCondition()->equal('mysql', '1');
            $field->name = "var[mysqlpwuser]";
            $field->label = "Database 'app' user password";
            $field->defaultValue = RandomGenerator::getRandomString(5, 10);
            $form->addField($field);

            $field = new Toggle();
            $field->name = "backup";
            $field->label = "Include daily automatic MySQL database backup schedule";
            $field->labelDescription = 'Defaults to 03:00 am, you can change it manually in the generated docker-compose.yml';
            $field->getVisibilityCondition()->equal('mysql', '1');
            $form->addField($field);

            $field = new Text();
            $field->required = true;
            $field->name = "var[port]";
            $field->label = "Public Port";
            $field->maxLength = 5;
            $field->maxWidth = 100;
            $field->defaultValue = "6456";
            $form->addField($field);

            $field = new Toggle();
            $field->name = "devmode";
            $field->label = "Development Mode";
            $field->labelDescription = 'Can be overriden with config later. Required for some automated file generators.';
            $field->defaultValue = 1;
            $form->addField($field);

            $field = new Toggle();
            $field->name = "framelixmount";
            $field->label = "Mount Framelix module";
            $field->labelDescription = 'This allow you to do modifications in the core module that are mapped back to the container. By default, the Framelix module folder is not mounted (The integrated one in the image is taken). You can change that later at any time by removed the comment "#" from the compose file for the Framelix mount.';
            $form->addField($field);

            $field = new Number();
            $field->name = "var[includeDocs]";
            $field->label = "Start docs application at port";
            $field->labelDescription = 'Each Framelix docker image have the complete docs also included (The one you currently see). Enable this to add a second port from where you can open the docs pages.';
            $field->placeholder = "e.g: 6457";
            $form->addField($field);

            $versionNumber = substr(Framelix::VERSION, 0, 1);
            $version = $versionNumber !== 'd' ? $versionNumber : 'dev';
            $field = new Select();
            $field->required = true;
            $field->name = "var[imagename]";
            $field->label = "Framelix Version";
            $field->addOption('nullixat/framelix:' . $version, 'nullixat/framelix:' . $version);
            $field->defaultValue = 'nullixat/framelix:' . $version;
            $form->addField($field);

            $form->addSubmitButton(buttonText: 'Generate docker-compose.yml');
            $form->show();
        }
    }

    public function showContent(): void
    {
        ?>
        <p>
            Development in Framelix is basically split into <code>modules</code>.
            One application as just one module, by default.
            So, here we are showing you how you setup for your first application module in Framelix.
            Framelix has a docker image that is ready to kickstart and what contains everything you need to begin
            developing.
        </p>
        <?= $this->getAnchoredTitle('requirements', 'Requirements') ?>
        <p>
            You need <?= $this->getLinkToExternalPage('https://www.docker.com/', 'Docker installed') ?>.<br/>
            On Windows you need to run
            everything <?= $this->getLinkToExternalPage(
                'https://ubuntu.com/tutorials/install-ubuntu-on-wsl2-on-windows-10#1-overview',
                'inside WSL'
            ) ?>. It is recommended to use the Ubuntu image for WSL with Docker Desktop installed.
        </p>
        <?= $this->getAnchoredTitle('setup', 'Setup') ?>
        <p>
            Create an empty folder somewhere and open a command line to it.<br/>
            Run the following commands. This will start the Framelix container and extract you the core and a
            starter module from the image, to provide you full autocompletion support and a minimal module to start
            with.
        </p>
        <?= $this->getAnchoredTitle('compose', 'Get your docker-compose.yml') ?>
        <p>
            At first you need a docker-compose.yml. Click the button bellow to generate one
        </p>
        <framelix-button jscall-url="<?= JsCall::getUrl(__CLASS__, 'create-config') ?>" theme="primary"
                         icon="draft_orders" target="modal">Click here to create your docker-compose.yml
        </framelix-button>

        <?= $this->getAnchoredTitle('start', 'Start container') ?>
        <p>The code bellow extract the code for the <code>Framelix (Core)</code> and <code>FramelixStarter</code> module
            from the docker
            image to your appdata directory.
            The module <code>Framelix</code> is by default "read-only" (It is not mapped from host to container). It is
            only for your IDE auto-completion in the first place.
            If you want quick hack into the core, just uncomment the prepared mapping line in docker-compose.yml.
            However, if you want help develop the core itself, head to this page.
        </p>
        <?php
        $this->showCodeBlock(
            '
        mkdir -p ./appdata/modules/Framelix ./appdata/modules/FramelixStarter userdata
        export FRAMELIX_APPDATA_MOUNT=appdata_dev
        docker compose down
        docker compose rm -f
        docker compose create
        docker compose cp app:/framelix/appdata/modules/Framelix ./appdata/modules/
        docker compose cp app:/framelix/appdata/modules/FramelixStarter ./appdata/modules/
        unset FRAMELIX_APPDATA_MOUNT
        docker compose up -d  
        echo "Now open https://127.0.0.1:$PORT_FROM_CONFIG in your browser and follow setup in the web interface"
        ',
            downloadFilename: "framelix-starter-install.sh"
        );
        ?>

        <?= $this->getAnchoredTitle('recommendations', 'Recommendations') ?>
        <p>
            Our favorite IDE is PhpStorm and Framelix is basically developed only with this. It provides industry
            leading autocompletion and so many other features, which makes development so much faster and easier. We are
            not affiliated with this IDE or company, it's just our recommendation.
        </p>
        <?= $this->getAnchoredTitle('older-docs', 'Get older version of this docs') ?>
        <p>
            We guess you only will need older docs when you actually using older versions of Framelix.
            For this, all Framelix docker images have the corresponding docs fully integrated.
            All you need to do is to enable them on a port, to be accessable.
            Use the docker-compose generator above to get a docker-compose.yml where the docs module is enabled and
            accessable. At the end, just modify the version to your needs in the final generated .yml file and start the
            project.
        </p>
        <?php
    }
}