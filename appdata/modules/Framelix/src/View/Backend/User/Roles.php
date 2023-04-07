<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\Config;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

class Roles extends View
{
    protected string|bool $accessRole = "admin";
    private User $storable;

    public function onRequest(): void
    {
        $this->storable = User::getByIdOrNew(Request::getGet('id'));
        if (!$this->storable->id) {
            $this->showInvalidUrlError();
        }
        if (Form::isFormSubmitted("roles")) {
            $form = $this->getForm();
            $form->validate();
            $roles = Config::$userRoles;
            foreach ($roles as $role => $row) {
                if (Request::getPost("role[$role]")) {
                    $this->storable->addRole($role);
                } else {
                    $this->storable->removeRole($role);
                }
            }
            // check if at least one admin exist
            $admins = User::getByCondition(
                Sql::get()->getConditionJsonContainsArrayValue('roles', '$', 'admin') .
                " && id != " . $this->storable
            );
            if (!$admins && !User::hasRole("admin", $this->storable)) {
                Response::stopWithFormValidationResponse('__framelix_user_edituser_validation_adminrequired__');
            }
            $this->storable->store();
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->setParameter('id', $this->storable)->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton();
        $form->show();
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "roles";

        $roles = Config::$userRoles;
        foreach ($roles as $role => $row) {
            $field = new Toggle();
            $field->name = "role[$role]";
            $field->label = $row['langKey'];
            $field->defaultValue = User::hasRole($role, $this->storable);
            $form->addField($field);
        }

        return $form;
    }
}