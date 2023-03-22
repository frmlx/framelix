<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

class Index extends View
{
    protected string|bool $accessRole = "admin,usermanagement";
    private User $storable;

    public function onRequest(): void
    {
        if ($simulateUser = Request::getGet('simulateUser')) {
            $user = User::getById($simulateUser);
            if ($user) {
                $token = UserToken::getByCookie();
                $token->simulatedUser = $user;
                $token->store();
                Toast::success(Lang::get('__framelix_simulateuser_done_', [$user->email]));
                Url::getBrowserUrl()->removeParameter('simulateUser')->redirect();
            }
        }
        $this->storable = User::getByIdOrNew(Request::getGet('id'));
        if ($this->storable->id) {
            $this->pageTitle = $this->storable->getHtmlString();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $tabs = new Tabs();
        $tabs->addTab('basic', null, new Basic());
        if ($this->storable->id) {
            $tabs->addTab('password', null, new Password());
            $tabs->addTab('roles', null, new Roles());
        }
        $tabs->show();
    }
}