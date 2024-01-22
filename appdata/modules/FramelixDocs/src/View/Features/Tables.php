<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Html\Table;
use Framelix\FramelixDocs\View\View;

class Tables extends View
{
    protected string $pageTitle = 'Tables';

    public static function basicTable(): void
    {
        $table = new Table();
        $table->createHeader([
            'id' => 'ID',
            'name' => 'Name',
            'timestamp' => 'Timestamp',
        ]);
        for ($i = 1; $i <= 30; $i++) {
            $table->createRow([
                'id' => $i,
                'name' => 'My number name is ' . $i,
                'timestamp' => DateTime::create('now + ' . $i . ' days'),
            ]);
        }
        $table->initialSort = ["+id"];
        $table->addColumnFlag(
            'id',
            Table::COLUMNFLAG_SMALLFONT,
            Table::COLUMNFLAG_SMALLWIDTH
        ); // to make the column smaller font
        $table->addColumnFlag(
            'timestamp',
            Table::COLUMNFLAG_SMALLFONT,
            Table::COLUMNFLAG_SMALLWIDTH
        ); // to make the column smaller font
        $table->show();
    }

    public static function dragSort(): void
    {
        $table = new Table();
        $table->createHeader([
            'id' => 'ID',
            'name' => 'Name',
            'timestamp' => 'Timestamp',
        ]);
        for ($i = 1; $i <= 30; $i++) {
            $table->createRow([
                'id' => $i,
                'name' => 'My number name is ' . $i,
                'timestamp' => DateTime::create('now + ' . $i . ' days'),
            ]);
        }
        $table->initialSort = ["+id"];
        $table->dragSort = true;
        $table->show();
        ?>
        <script>
          (function () {
            const table = FramelixTable.getById('<?=$table->id?>')
            table.container.on(FramelixTable.EVENT_COLUMNSORT_SORT_CHANGED, function () {
              FramelixToast.success('Sort with header sort changed')
            })
            table.container.on(FramelixTable.EVENT_DRAGSORT_SORT_CHANGED, function () {
              FramelixToast.success('Sort with mouse drag&drop changed')
            })
          })()
        </script>
        <?php
    }

    public static function checkboxes(): void
    {
        $table = new Table();
        $table->createHeader([
            'id' => 'ID',
            'name' => 'Name',
            'timestamp' => 'Timestamp',
        ]);
        for ($i = 1; $i <= 30; $i++) {
            $table->createRow([
                'id' => $i,
                'name' => 'My number name is ' . $i,
                'timestamp' => DateTime::create('now + ' . $i . ' days'),
            ]);
        }
        $table->initialSort = ["+id"];
        $table->checkboxColumn = true;
        $table->show();
    }

    public function showContent(): void
    {
        ?>
        <p>
            Tables in Framelix are an important part, as Framelix is basically a data managament Framework in it's core.
            Tables are the most used way to display a list of entries.
            It includes many features like client side table sort, drag&drop row sort, dynamic adding data and rows,
            checkboxes to mark rows and many more.
            <br/>
            The feature uses both Javascript for frontend rendering and PHP for backend data management.
            <br/>
            You generally don't need to create any Javascript code to render tables. Everything is created and rendered
            automatically, out of your table data you define with PHP.
            Javascript is later available to modify the table at runtime, if required.<br/>
            Let's see some examples.
        </p>
        <p>
            Tables are even more powerful in combination with <code>StorableMeta</code> and <code>Storables</code>, as
            they can be auto-generated, with all properties that are available from the database, with a few lines of
            code.
        </p>
        <?php
        $this->addPhpExecutableMethod([__CLASS__, "basicTable"],
            "Basic Table",
            "A pretty basic table just displayed some rows with sortable headers and some columns format overriden.");
        $this->addPhpExecutableMethod([__CLASS__, "dragSort"],
            "Drag & Drop Sort",
            "Use your mouse to drag and drop rows. In javascript you can listen to that events and do something after sort have changed."
        );
        $this->addPhpExecutableMethod([__CLASS__, "checkboxes"],
            "Selectable checkboxes",
            "Adds checkboxes to the rows, so an user can select rows.");
        $this->showPhpExecutableMethodsCodeBlock();
        ?>
        <p>
            You can read out more features, flags and functions directly from source
            at <?= $this->getSourceFileLinkTag(
                ['Framelix/js/framelix-table.js', 'Framelix/src/Html/Table.php']
            ) ?>.
        </p>
        <?php
    }
}