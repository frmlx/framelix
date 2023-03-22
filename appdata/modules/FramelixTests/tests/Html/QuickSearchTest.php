<?php

namespace Html;

use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Html\QuickSearch;
use Framelix\Framelix\Html\Table;
use Framelix\FramelixTests\TestCase;

final class QuickSearchTest extends TestCase
{

    public function tests(): void
    {
        $quickSearch = new QuickSearch();
        $quickSearch->assignedTable = new Table();
        $this->callMethodsGeneric($quickSearch, ['addOptionField', 'addOptionFields', 'addOptionsFields']);

        $field = new Text();
        $field->name = "test";
        $fields = [$field];
        $quickSearch->addOptionFields($fields);
    }
}
