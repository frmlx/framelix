<?php

namespace Framelix\FramelixDocs;

use Framelix\Framelix\View\Backend\View;

use const FRAMELIX_MODULE;
use const FRAMELIX_USERDATA_FOLDER;

class Config
{

    public static string $demoAppUrl = 'https://127.0.0.1:6104';

    public static string $slackApiToken = '';

    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::addSqliteConnection(
          FRAMELIX_MODULE,
          FRAMELIX_USERDATA_FOLDER . "/sqlite_" . FRAMELIX_MODULE . ".db"
        );
        \Framelix\Framelix\Config::$languagesAvailable = ['en'];
        \Framelix\Framelix\Config::$backendLogoFilePath = __DIR__ . "/../public/images/logo.jpg";

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "scss", "docs");
        $bundle->includeInBackendView = \Framelix\FramelixDocs\View\View::class;
        $bundle->addFile("scss/docs.scss");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "scss", "landing");
        $bundle->addFile("scss/landing.scss");
        $bundle->addFile("../Framelix/scss/general/icon-font.scss");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "scss", "vendor");
        $bundle->includeInBackendView = \Framelix\FramelixDocs\View\View::class;
        $bundle->compile = false;
        $bundle->addFile("vendor/highlightjs/default.min.css");
        $bundle->addFile("vendor/highlightjs/atom-one-dark-reasonable.min.css");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "js", "docs");
        $bundle->includeInBackendView = \Framelix\FramelixDocs\View\View::class;
        $bundle->addFile("js/docs.js");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "js", "vendor");
        $bundle->includeInBackendView = \Framelix\FramelixDocs\View\View::class;
        $bundle->compile = false;
        $bundle->addFile("vendor/highlightjs/highlight.min.js");
        $bundle->addFile("vendor/highlightjs/highlightjs-line-numbers.min.js");
    }

}