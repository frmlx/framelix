<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Db\StorableSchemaProperty;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Html\TypeDefs\ElementColor;
use Framelix\Framelix\Html\TypeDefs\JsRequestOptions;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\ObjectTransformable;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Time;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\Utils\StringUtils;
use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;

use function array_combine;
use function array_keys;
use function array_unshift;
use function array_values;
use function get_class;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function mb_strtolower;
use function reset;
use function str_starts_with;
use function trim;

/**
 * Table
 */
class Table implements JsonSerializable
{
    /**
     * No special behaviour
     * @var string
     */
    public const string COLUMNFLAG_DEFAULT = 'default';

    /**
     * An icon column which takes the complete space of a column and display a clickable button with that icon
     * @var string
     */
    public const string COLUMNFLAG_ICON = 'icon';

    /**
     * Use smallest width possible, depending on the content
     * @var string
     */
    public const string COLUMNFLAG_SMALLWIDTH = 'smallwidth';

    /**
     * Use a smaller font
     * @var string
     */
    public const string COLUMNFLAG_SMALLFONT = 'smallfont';

    /**
     * Ignore sort for this column
     * @var string
     */
    public const string COLUMNFLAG_IGNORESORT = 'ignoresort';

    /**
     * Ignore click and link to url on this column
     * @var string
     */
    public const string COLUMNFLAG_IGNOREURL = 'ignoreurl';

    /**
     * Remove the column if all cells in the tbody are empty
     * @var string
     */
    public const string COLUMNFLAG_REMOVE_IF_EMPTY = 'removeifempty';

    /**
     * Id for the table
     * Default is random generated in constructor
     * @var string
     */
    public string $id;

    /**
     * The column order in which order the columns are displayed
     * Automatically set by first added row
     * @var array
     */
    public array $columnOrder = [];

    /**
     * If you want to sum columns in the footer, set the columns here
     * @var array|null
     */
    public ?array $footerSumColumns = null;

    /**
     * The rows internal data
     * Grouped by thead/tbody/tfoot
     * @var array
     */
    protected array $rows = [];

    /**
     * Is the table sortable
     * @var bool
     */
    public bool $sortable = true;

    /**
     * The initial sort
     * Array value is cellName prefixed with +/- (+ = ASC, - = DESC)
     * Example: ["+cellName", "-cellName"]
     * @var string[]
     */
    public ?array $initialSort = null;

    /**
     * Remember the sort settings in client based on the tables id
     * @var bool
     */
    public bool $rememberSort = true;

    /**
     * Add a checkbox column at the beginning
     * @var bool
     */
    public bool $checkboxColumn = false;

    /**
     * Add a column at the beginning, where the user can sort the table rows by drag/drop
     * @var bool
     */
    public bool $dragSort = false;

    /**
     * Let user store the sorting in the database for the new sorted storables
     * @var bool
     */
    public bool $storableSort = false;

    /**
     * General flag if the generated table has deletable button for a storable row
     * If true then it also depends on the storable isDeletable return value
     * @var bool
     */
    public bool $storableDeletable = true;

    /**
     * If a row has an url attached, open in a new tab instead of current tab
     * @var bool
     */
    public bool $urlOpenInNewTab = false;

    /**
     * Column flags
     * @var array
     */
    public array $columnFlags = [];

    /**
     * Include some html before <table>
     * @var string|null
     */
    public ?string $prependHtml = null;

    /**
     * Include some html before <table>
     * @var string|null
     */
    public ?string $appendHtml = null;

    /**
     * Escape html inside table cells
     * true = escape every cell
     * false = escape no cell
     * array = escape only given cell names
     * @var bool|string[]
     */
    public bool|array $escapeHtml = true;

    /**
     * Row url open method
     * default = Same browser window or new window when user click with middle mouse button
     * newwindow = New browser window (tab)
     * currenttab = If table is in a FramelixTab, then load it into this tab - If is no tab, it falls back to default
     * currentmodal = If table is in a FramelixModal, then load it into this modal - If is no tab, it falls back to default
     * newmodal = Opens the row url in a new FramelixModal
     * @var string
     */
    #[ExpectedValues(values: ["default", "newwindow", "currenttab", "currentmodal", "newmodal"])]
    public string $rowUrlOpenMethod = 'default';

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action == 'storableSort') {
            $data = $jsCall->parameters['data'] ?? null;
            if (!is_array($data) || !$data) {
                return;
            }
            $firstRow = reset($data);
            $objects = Storable::getByIds(
                ArrayUtils::map($data, "0"),
                $firstRow[1] ?? null
            );
            $sort = 0;
            foreach ($objects as $object) {
                if (!Storable::getStorableSchemaProperty($object, "sort")) {
                    throw new FatalError(
                        'Missing "sort" property on ' . get_class($object)
                    );
                }
                /** @phpstan-ignore-next-line */
                $object->{"sort"} = $sort++;
                if ($object instanceof StorableExtended) {
                    $object->preserveUpdateUserAndTime();
                }
                $object->store();
            }
            $jsCall->result = true;
        }
    }

    /**
     * Show table
     */
    final public function show(): void
    {
        PhpToJsData::renderToHtml($this->jsonSerialize());
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = RandomGenerator::getRandomHtmlId();
    }

    /**
     * Create a new thead row with given cell values
     * @param array $values
     * @return int The row key
     */
    public function createHeader(array $values): int
    {
        if (!isset($this->rows['thead'])) {
            $this->rows['thead'] = [];
        }
        $rowKey = count($this->rows['thead']);
        $this->setRowValues($rowKey, $values, "thead");
        return $rowKey;
    }

    /**
     * Create a new tbody row with given cell values
     * @param array $values
     * @return int The row key
     */
    public function createRow(array $values): int
    {
        if (!isset($this->rows['tbody'])) {
            $this->rows['tbody'] = [];
        }
        $rowKey = count($this->rows['tbody']);
        $this->setRowValues($rowKey, $values);
        return $rowKey;
    }

    /**
     * Create a new footer row with given cell values
     * @param array $values
     * @return int The row key
     */
    public function createFooter(array $values): int
    {
        if (!isset($this->rows['tfoot'])) {
            $this->rows['tfoot'] = [];
        }
        $rowKey = count($this->rows['tfoot']);
        $this->setRowValues($rowKey, $values, "tfoot");
        return $rowKey;
    }

    /**
     * Set/override cell values for given row
     * @param int $rowKey
     * @param array $values
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setRowValues(int $rowKey, array $values, string $group = "tbody"): void
    {
        if (!$this->columnOrder) {
            $this->columnOrder = array_keys($values);
        }
        foreach ($values as $cellName => $value) {
            $this->setCellValue($rowKey, $cellName, $value, null, $group);
        }
    }

    /**
     * Get assigned row storable
     * @param int $rowKey
     * @param string $group The group: thead, tbody, tfoot
     * @return Storable|null
     */
    public function getRowStorable(int $rowKey, string $group = "tbody"): ?Storable
    {
        return $this->rows[$group][$rowKey]['storable'] ?? null;
    }

    /**
     * Assign a storable to a row, setting some defaults for this row
     * @param int $rowKey
     * @param Storable $storable
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setRowStorable(int $rowKey, Storable $storable, string $group = "tbody"): void
    {
        $this->rows[$group][$rowKey]['storable'] = $storable;
        if ($storable->isReadable()) {
            $this->setRowUrl($rowKey, $storable->getDetailsUrl(), $group);
        }
        if ($this->storableDeletable && $storable->isDeletable()) {
            $deleteUrl = $storable->getDeleteUrl(Url::getBrowserUrl());
            $cell = null;
            if($deleteUrl){
                $requestOptions = new JsRequestOptions($deleteUrl);
                $cell = TableCell::create('<framelix-button icon="732" bgcolor="hsla(360,65%,var(--color-default-contrast-bg),0.5)" textcolor="white" confirm-message="__framelix_delete_sure__" request-options=\''.$requestOptions.'\'></framelix-button>');
            }
            if (!in_array("_deletable", $this->columnOrder)) {
                array_unshift($this->columnOrder, "_deletable");
            }
            $this->setCellValue($rowKey, "_deletable", $cell, null, $group);
        }
        $attributes = ['data-id' => $storable];
        if ($storable->connectionId !== 'default') {
            $attributes['data-connection-id'] = $storable->connectionId;
        }
        $this->getRowHtmlAttributes($rowKey)->setArray($attributes);
    }

    /**
     * Assign a url to a row, where the user can click on the row to open the url
     * @param int $rowKey
     * @param Url|null $url
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setRowUrl(int $rowKey, ?Url $url, string $group = "tbody"): void
    {
        $this->getRowHtmlAttributes($rowKey, $group)->set('data-url', $url);
    }

    /**
     * Get row html attributes
     * @param int $rowKey
     * @param string $group The group: thead, tbody, tfoot
     * @return HtmlAttributes
     */
    public function getRowHtmlAttributes(int $rowKey, string $group = "tbody"): HtmlAttributes
    {
        if (!isset($this->rows[$group][$rowKey]['htmlAttributes'])) {
            $this->rows[$group][$rowKey]['htmlAttributes'] = new HtmlAttributes();
        }
        return $this->rows[$group][$rowKey]['htmlAttributes'];
    }

    /**
     * Set override a value of a single cell
     * @param int $rowKey
     * @param mixed $columnName
     * @param mixed $value
     * @param mixed $sortValue Explicit value to be used by the tablesorter, null does try to auto detect
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setCellValue(
        int $rowKey,
        mixed $columnName,
        mixed $value,
        mixed $sortValue = null,
        string $group = "tbody"
    ): void {
        if (!isset($this->rows[$group][$rowKey])) {
            $this->rows[$group][$rowKey]['rowKeyInitial'] = $rowKey;
        }
        // auto detect column flag if framelix button is assed
        if (
            $value instanceof TableCell
            && is_string($value->stringValue)
            && str_starts_with($value->stringValue, '<framelix-button')) {
            $this->addColumnFlag(
                $columnName,
                self::COLUMNFLAG_ICON,
                self::COLUMNFLAG_IGNORESORT,
                self::COLUMNFLAG_IGNOREURL,
                self::COLUMNFLAG_REMOVE_IF_EMPTY
            );
            if ($sortValue === null) {
                $sortValue = $value->sortValue;
            }
        }
        $this->rows[$group][$rowKey]['cellValues'][$columnName] = $value;
        $this->rows[$group][$rowKey]['sortValues'][$columnName] = $sortValue;
    }

    /**
     * Set cell html attributes
     * @param int $rowKey
     * @param mixed $cellName
     * @param HtmlAttributes $attributes
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setCellHtmlAttributes(
        int $rowKey,
        mixed $cellName,
        HtmlAttributes $attributes,
        string $group = "tbody"
    ): void {
        $this->rows[$group][$rowKey]['cellAttributes'][$cellName] = $attributes;
    }

    /**
     * Get cell html attributes
     * @param int $rowKey
     * @param mixed $cellName
     * @param string $group The group: thead, tbody, tfoot
     * @return HtmlAttributes
     */
    public function getCellHtmlAttributes(int $rowKey, mixed $cellName, string $group = "tbody"): HtmlAttributes
    {
        if (!isset($this->rows[$group][$rowKey]['cellAttributes'][$cellName])) {
            $this->rows[$group][$rowKey]['cellAttributes'][$cellName] = new HtmlAttributes();
        }
        return $this->rows[$group][$rowKey]['cellAttributes'][$cellName];
    }

    /**
     * Add a column flag
     * @param string $columnName
     * @param string ...$columnFlags COLUMNFLAG_*
     */
    public function addColumnFlag(
        string $columnName,
        #[ExpectedValues(valuesFromClass: self::class)] string ...$columnFlags
    ): void {
        if (!isset($this->columnFlags[$columnName])) {
            $this->columnFlags[$columnName] = [];
        }
        foreach ($columnFlags as $columnFlag) {
            if (!in_array($columnFlag, $this->columnFlags[$columnName])) {
                $this->columnFlags[$columnName][] = $columnFlag;
            }
        }
    }

    /**
     * Get json data
     * @return PhpToJsData
     */
    public function jsonSerialize(): PhpToJsData
    {
        $properties = [];
        foreach ($this as $key => $value) {
            $properties[$key] = $value;
        }
        $properties['storableSortJsCallUrl'] = JsCall::getUrl(__CLASS__, 'storableSort');
        if ($this->footerSumColumns) {
            foreach ($this->footerSumColumns as $columnName) {
                if (!in_array($columnName, $this->columnOrder)) {
                    throw new FatalError(
                        'Cell "' . $columnName . '" for footerSumColumns does not exist'
                    );
                }
            }
        }
        if ($this->footerSumColumns) {
            $this->footerSumColumns = array_combine($this->footerSumColumns, $this->footerSumColumns);
        }
        $convertValue = function (mixed $value, ?StorableSchemaProperty $storableSchemaProperty, &$sortValue) use (
            &$convertValue
        ) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = $convertValue($v, $storableSchemaProperty, $sortValue);
                }
                return array_values($value);
            }
            if (is_float($value) && ($storableSchemaProperty->decimals ?? 0) > 0) {
                $value = NumberUtils::format($value, $storableSchemaProperty?->decimals);
            }
            if ($sortValue === null) {
                if ($value instanceof ObjectTransformable) {
                    $sortValue = $value->getSortableValue();
                } elseif (is_int($value) || is_float($value) || is_bool($value)) {
                    $sortValue = is_bool($value) ? (int)$value : $value;
                }
            }
            if ($value instanceof ObjectTransformable) {
                $htmlValue = $value->getHtmlTableValue();
                if ($htmlValue instanceof TableCell) {
                    $value = $htmlValue;
                } else {
                    $tableCell = new TableCell();
                    $tableCell->sortValue = $value->getSortableValue();
                    $tableCell->stringValue = $htmlValue;
                    $value = $tableCell;
                }
            }
            if (!$value instanceof TableCell) {
                $value = trim(StringUtils::stringify($value, "<br/>"));
                if ($sortValue === null) {
                    $sortValue = StringUtils::slugify(mb_strtolower($value));
                }
            }
            return $value;
        };
        $footerSums = [];
        foreach ($properties['rows'] as $group => $rows) {
            foreach ($rows as $rowKey => $rowValues) {
                if (!isset($rowValues['cellValues'])) {
                    continue;
                }
                $storable = $this->getRowStorable($rowKey);
                foreach ($rowValues['cellValues'] as $columnName => $value) {
                    $storableSchemaProperty = $storable ? Storable::getStorableSchemaProperty(
                        $storable,
                        $columnName
                    ) : null;
                    if ($group === 'tbody' && isset($this->footerSumColumns[$columnName])) {
                        if (!isset($footerSums[$columnName])) {
                            $footerSums[$columnName] = ['value' => 0, 'type' => 'default'];
                        }
                        if (is_string($value)) {
                            $value = NumberUtils::toFloat($value);
                        }
                        if (is_int($value) || is_float($value)) {
                            $footerSums[$columnName]['type'] = 'number';
                            $footerSums[$columnName]['decimals'] = $storableSchemaProperty->decimals ?? null;
                            $footerSums[$columnName]['value'] += $value;
                        } elseif ($value instanceof Time) {
                            $footerSums[$columnName]['type'] = "time";
                            $footerSums[$columnName]['value'] = round(
                                $footerSums[$columnName]['value'] + Time::toHours($value),
                                4
                            );
                        }
                    }
                    $sortValue = $rowValues['sortValues'][$columnName] ?? null;
                    $value = $convertValue($value, $storableSchemaProperty, $sortValue);
                    $rowValues['cellValues'][$columnName] = $value;
                    $rowValues['sortValues'][$columnName] = $sortValue;
                    $properties['rows'][$group][$rowKey] = $rowValues;
                }
            }
        }
        if ($footerSums) {
            if (!isset($properties['rows']['tfoot'])) {
                $properties['rows']['tfoot'] = [];
            }
            $row = [];
            foreach ($footerSums as $columnName => $rowValues) {
                if ($rowValues['type'] === 'time') {
                    $row[$columnName] = Time::hoursToTimeString($rowValues['value']);
                } else {
                    $row[$columnName] = NumberUtils::format($rowValues['value'], $rowValues['decimals'] ?? 2);
                }
            }
            $properties['rows']['tfoot'][]['cellValues'] = $row;
        }
        return new PhpToJsData($properties, $this, 'FramelixTable');
    }

}