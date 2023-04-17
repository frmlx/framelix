<?php

namespace Framelix\FramelixDocs\View\GetStarted;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
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
                if (Request::getPost('framelixmount')) {
                    $contents = preg_replace("~^#( .*?appdata/modules/Framelix.*$)~m", '$1', $contents);
                }
                $contents = str_replace(['# mariadb-start', '# mariadb-end'], '', $contents);
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
        <blockquote>Notice: The extracted module <code>Framelix</code> inside <code>appdata</code> is basically
            "read-only". It is not mapped from host to container by default. If you want quick hack into the core, just
            map the <code>appdata/modules/Framelix</code> folder as well in the <code>docker run</code>. However, if you
            want help develop the core itself, head to this page.
        </blockquote>
        <framelix-button jscall-url="<?= JsCall::getUrl(__CLASS__, 'create-config') ?>" theme="primary"
                         icon="draft_orders" target="modal">Click here to create your docker-compose.yml
        </framelix-button>
        <p>
            With the generated <code>docker-compose.yml</code> in this folder now run the following.
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
        <?php
    }
}