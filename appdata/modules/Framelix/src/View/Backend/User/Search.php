<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\StorableMeta\User;
use Framelix\Framelix\View\Backend\View;

class Search extends View
{
    protected string|bool $accessRole = "admin,usermanagement";

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $userCount = Mysql::get()->fetchOne('SELECT COUNT(*) FROM `' . \Framelix\Framelix\Storable\User::class . '`');
        $meta = new User(new \Framelix\Framelix\Storable\User());
        $quickSearch = $meta->getQuickSearch();
        $quickSearch->forceInitialQuery = $userCount <= 50 ? "*" : null;
        $quickSearch->show();
    }
}