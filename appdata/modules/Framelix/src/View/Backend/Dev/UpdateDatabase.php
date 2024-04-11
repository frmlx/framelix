<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Console;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Db\SqlStorableSchemeBuilder;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\View\Backend\View;

use function sleep;

class UpdateDatabase extends View
{
    protected bool $devModeOnly = true;

    public function onRequest(): void
    {
        if (Request::getPost('safeQueriesExecute') || Request::getPost('unsafeQueriesExecute')) {
            if (Request::getPost('safeQueriesExecute')) {
                // wait 3 seconds to prevent opcache in default configs
                sleep(3);
                $shell = Console::callMethodInSeparateProcess('updateDatabaseSafe');
                Toast::info(Shell::convertCliOutputToHtml($shell->output, true));
            }
            if (Request::getPost('unsafeQueriesExecute')) {
                // wait 3 seconds to prevent opcache in default configs
                sleep(3);
                $shell = Console::callMethodInSeparateProcess('updateDatabaseUnsafe');
                Toast::info(Shell::convertCliOutputToHtml($shell->output, true));
            }
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton("update", "__framelix_view_backend_dev_updatedatabase_updatenow__");
        $form->show();
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "update-database";

        $builder = new SqlStorableSchemeBuilder(Sql::get());
        $unsafeQueries = $builder->getUnsafeQueries();
        $safeQueries = $builder->getSafeQueries();

        if (!$unsafeQueries && !$safeQueries) {
            $field = new Html();
            $field->name = "fine";
            $field->defaultValue = '<framelix-alert theme="success">Everything is fine</framelix-alert>';
            $form->addField($field);
        } else {
            if ($safeQueries) {
                $field = new Html();
                $field->name = "safeQueriesHtml";
                $field->label = '__framelix_view_backend_dev_updatedatabase_safequeries__';
                $field->defaultValue = '';
                foreach ($safeQueries as $row) {
                    $field->defaultValue .= '<div class="framelix-code-block">' . HtmlUtils::escape(
                            $row['query']
                        ) . ';</div>';
                }
                $form->addField($field);

                $field = new Toggle();
                $field->name = "safeQueriesExecute";
                $field->label = '__framelix_view_backend_dev_updatedatabase_safequeries_execute__';
                $field->defaultValue = true;
                $form->addField($field);
            }

            if ($unsafeQueries) {
                $field = new Html();
                $field->name = "unsafeQueriesHtml";
                $field->label = '__framelix_view_backend_dev_updatedatabase_unsafequeries__';
                $field->defaultValue = '';
                foreach ($unsafeQueries as $row) {
                    $field->defaultValue .= '<div class="framelix-code-block">' . HtmlUtils::escape(
                            $row['query']
                        ) . ';</div>';
                }
                $form->addField($field);

                $field = new Toggle();
                $field->name = "unsafeQueriesExecute";
                $field->label = '__framelix_view_backend_dev_updatedatabase_unsafequeries_execute__';
                $form->addField($field);
            }
        }
        return $form;
    }
}