<?php

namespace Framelix\FramelixDemo;

use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixDemo\View\Index;
use Framelix\FramelixDemo\View\Outgoings;

use const FRAMELIX_MODULE;

class Config
{
    /**
     * The unit symbol which this application uses
     * @var string
     */
    public static string $moneyUnit = "€";

    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::$backendDefaultView = Index::class;
        \Framelix\Framelix\Config::$backendLogoFilePath = __DIR__ . "/../public/img/logo.png";
        \Framelix\Framelix\Config::$backendFaviconFilePath = __DIR__ . "/../public/img/logo-squared.png";
        \Framelix\Framelix\Config::addAvailableUserRole('outgoing', '__framelixdemo_view_outgoings__');
        \Framelix\Framelix\Config::addAvailableUserRole('income', '__framelixdemo_view_incomes__');
        \Framelix\Framelix\Config::addAvailableUserRole('invoice-1', '__framelixdemo_view_invoice_category_1__');
        \Framelix\Framelix\Config::addAvailableUserRole('invoice-2', '__framelixdemo_view_invoice_category_2__');
        \Framelix\Framelix\Config::addAvailableUserRole('fixation', '__framelixdemo_view_fixations__');
        \Framelix\Framelix\Config::addAvailableUserRole('depreciation', '__framelixdemo_view_depreciation__');
        \Framelix\Framelix\Config::addAvailableUserRole('reports', '__framelixdemo_view_reports__');

        \Framelix\Framelix\Config::addSqliteConnection(
            FRAMELIX_MODULE,
            FileUtils::getUserdataFilepath("database.db", false)
        );

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDemo", "js", "framelixdemo");
        $bundle->addFolder("vendor-frontend/js", true);

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDemo", "scss", "framelixdemo");
        $bundle->addFolder("vendor-frontend/scss", true);
    }


    /**
     * Get form that allow config values to be edited via admin web interface
     * All config keys that not have a field with this name will not be editable in the UI
     * @return Form
     */
    public static function getEditableConfigForm(): Form
    {
        $form = new Form();

        $field = new Text();
        $field->name = "moneyUnit";
        $field->required = true;
        $field->maxWidth = 50;
        $field->maxLength = 10;
        $form->addField($field);

        return $form;
    }
}