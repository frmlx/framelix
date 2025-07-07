<?php

namespace Framelix\Framelix\Utils;

use avadim\FastExcelHelper\Helper;
use avadim\FastExcelWriter\Excel;
use avadim\FastExcelWriter\Sheet;
use avadim\FastExcelWriter\Style;
use Closure;
use Exception;
use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Network\Response;

use function is_array;
use function is_bool;
use function is_numeric;
use function is_object;
use function is_string;
use function mb_substr;
use function preg_replace_callback;
use function str_contains;
use function strlen;
use function substr;

/**
 * Fast excel wrapper
 * Doc at https://github.com/aVadim483/fast-excel-writer
 */
class FastExcelWrapper
{

    private static ?string $tmpDir = null;

    /**
     * @var self[]|null
     */
    private static array|null $instances = [];

    public static function readFileToArray(string $path, int|string|null $sheet = null): array
    {
        $excel = \avadim\FastExcelReader\Excel::open($path);
        $excel->dateFormatter(true);
        if (is_int($sheet)) {
            $excel->selectSheetById($sheet);
        } elseif (is_string($sheet)) {
            $excel->selectSheet($sheet);
        }
        return $excel->readRows();
    }

    /**
     * Convert any value to be usable in excel
     * This should only be used for excel context, not for anything else
     * @param mixed $value
     * @return mixed
     */
    public static function convertValue(mixed $value): mixed
    {
        if (is_array($value) && count($value) === 2 && isset($value[0]) && $value[0] === "FastExcelCell") {
            $cell = new FastExcelCell("");
            foreach ($value[1] as $k => $v) {
                $cell->{$k} = $v;
            }
            return $cell;
        }
        if ($value instanceof FastExcelCell) {
            $value = $value->cellValue;
        }
        if ($value instanceof Closure) {
            $value = $value();
        }
        if (is_array($value) || is_object($value) || is_bool($value)) {
            $value = StringUtils::stringify($value);
        }
        return $value;
    }

    public function __construct(public ?Excel $excel = null)
    {
        self::$instances[] = $this;
        if (!self::$tmpDir) {
            register_shutdown_function(function () {
                foreach (self::$instances as $instance) {
                    $instance->excel->writer->removeFiles();
                }
            });
            self::$tmpDir = FileUtils::getTmpFolder();
        }
        Excel::setTempDir(self::$tmpDir);
        if (!$this->excel) {
            $this->excel = Excel::create();
        }
    }

    /**
     * Set sheet values from array
     * You can call this multiple times on the same sheet
     * You can even call this for every single row if you need max memory efficiency
     * @param array $array
     * @param int|null $autoFilterRow Apply autofilter to the given row
     * @param bool $autoSize Automatic width of columns to fit in the content
     * @param bool $allowFormulas If false, than cells with formulas will be strings
     * @param int $wrapText
     *  1 = auto (Make all next same cell rows autowrap when any value has \n newline in it)
     *  2 = force (Every cell have wrap text)
     *  0 = disabled
     * @param Sheet|null $sheet
     * @param array|null $rowStyles Row Nr. start with 1
     *  Example: [1 => [Style::FONT_COLOR => '#ff0000']]
     * @param array|null $colStyles Col Nr. start with 1
     *  Write col coordinates in Excel Format (A) or as a number starting with A = 1
     *  Example: ["A" => [Style::FONT_COLOR => '#ff0000']]
     *  Example: [1 => [Style::FONT_COLOR => '#ff0000']]
     * @param array|null $cellStyles If set, this must contain style for a single cell,
     *  You can also use FastExcelCell as a cell value to modify the style directly with some handy functions
     *  Write cell coordinates in Excel Format (A2) or in comma separated row,col (2, 1)
     *  Example: ["2,1" => [Style::FONT_COLOR => '#ff0000']]
     *  Example: [Excel::cellAddress(2, 1) => [Style::FONT_COLOR => '#ff0000']]
     * @see FastExcelCell
     * @see https://github.com/aVadim483/fast-excel-writer/blob/master/docs/04-styles.md
     */
    public function setFromArray(
        array $array,
        ?int $autoFilterRow = null,
        bool $autoSize = false,
        bool $allowFormulas = false,
        int $wrapText = 0,
        Sheet|null $sheet = null,
        ?array $rowStyles = null,
        ?array $colStyles = null,
        ?array $cellStyles = null,
    ): void {
        if (!$array) {
            return;
        }
        $sheet = $sheet ?? $this->excel->sheet();
        $columnCount = count(reset($array));
        if ($autoSize) {
            for ($i = 1; $i <= $columnCount; $i++) {
                $sheet->setColWidthAuto($i);
            }
        }
        if ($colStyles) {
            foreach ($colStyles as $styleColNr => $style) {
                if (!is_numeric($styleColNr)) {
                    $styleColNr = Excel::colNumber($styleColNr);
                }
                $sheet->setColStyle($styleColNr, $style);
            }
        }
        $rowNr = $sheet->getCurrentRowId() + 1;
        $columnSettingsDone = [];
        $dataKeys = array_keys(reset($array));
        foreach ($array as $row) {
            $rowNr++;
            foreach ($dataKeys as $dataKey) {
                $value = $row[$dataKey] ?? null;
                $colNr = $sheet->getCurrentColId();
                $originalValue = $value;
                $value = self::convertValue($value);
                if ($value instanceof FastExcelCell) {
                    $originalValue = $value;
                    $value = $value->cellValue;
                }
                if (is_string($value)) {
                    $strlen = strlen($value);
                    if ($strlen >= 2 && $value[0] === " " && $value[1] === "=") {
                        $sheet->writeCell(mb_substr($value, 1));
                    } elseif ($strlen >= 1 && $value[0] === "=") {
                        if ($allowFormulas) {
                            if ($originalValue instanceof FastExcelCell && $originalValue->hasRelativeReference) {
                                $value = preg_replace_callback("~\\\$\\\$\\\$_\(([-0-9]+),([-0-9]+)\)~", function ($matches) use ($sheet) {
                                    return Helper::colLetter($sheet->getCurrentColId() + $matches[2] + 1) . (($sheet->getCurrentRowId() + 1) + $matches[1]);
                                }, $value);
                            }
                        } else {
                            $value = "\\" . $value;
                        }
                        $sheet->writeCell($value);
                    } elseif ($originalValue instanceof Date || $originalValue instanceof DateTime) {
                        $isDate = $originalValue instanceof Date;
                        $excelValue = $originalValue;
                        if ($isDate) {
                            $excelValue = $originalValue->getDbValue();
                        } elseif ($originalValue instanceof DateTime) {
                            $excelValue = $originalValue->getDbValue();
                        }
                        $sheet->writeCell($excelValue, ["format" => $isDate ? "DD.MM.YYYY" : "DD.MM.YYYY HH:MM:SS"]);
                        if (!isset($columnSettingsDone[$colNr])) {
                            $sheet->setColWidth($colNr, $isDate ? 12 : 20);
                            $columnSettingsDone[$colNr] = true;
                        }
                    } elseif (
                        (strlen($value) === 10 || strlen($value) === 19) &&
                        $value[4] === "-" && $value[7] === "-" &&
                        is_numeric(substr($value, 0, 4)) && is_numeric(substr($value, 5, 2)) && is_numeric(substr($value, 7, 2))
                    ) {
                        $isDate = false;
                        if (isset($value[16]) && $value[10] === " " && $value[13] === ":" && $value[16] === ":") {
                            $sheet->writeCell($value, ["format" => "DD.MM.YYYY HH:MM:SS"]);
                        } else {
                            $isDate = true;
                            $sheet->writeCell($value, ["format" => "DD.MM.YYYY"]);
                        }
                        if (!isset($columnSettingsDone[$colNr])) {
                            $sheet->setColWidth($colNr, $isDate ? 12 : 20);
                            $columnSettingsDone[$colNr] = true;
                        }
                    } else {
                        $sheet->writeCell($value);
                    }
                } elseif (is_numeric($value)) {
                    $sheet->writeCell($value);
                } else {
                    $sheet->writeCell($value);
                }
                // force wrap text when any value contains a line break
                if ($wrapText === 1 && is_string($value) && str_contains($value, "\n")) {
                    $wrapText = 2;
                    if (!isset($columnSettingsDone[$colNr])) {
                        $sheet->setColWidth($colNr, 50);
                        $columnSettingsDone[$colNr] = true;
                    }
                }
                $applyStyle = null;
                if ($originalValue instanceof FastExcelCell && $originalValue->style) {
                    $applyStyle = $originalValue->style;
                }
                if (is_string($value) && $wrapText === 2) {
                    $applyStyle[Style::FORMAT][Style::TEXT_WRAP] = true;
                }
                if ($cellStyles) {
                    $rowNr = $sheet->getCurrentRowId() + 1;
                    $styleColNr = $colNr + 1;
                    $cellAddr = $rowNr . "," . $styleColNr;
                    if (isset($cellStyles[$cellAddr])) {
                        $applyStyle = FastExcelCell::mergeStyle($applyStyle, $cellStyles[$cellAddr]);
                    } else {
                        $cellAddr = Excel::cellAddress($rowNr, $styleColNr);
                        if (isset($cellStyles[$cellAddr])) {
                            $applyStyle = FastExcelCell::mergeStyle($applyStyle, $cellStyles[$cellAddr]);
                        }
                    }
                }
                if ($applyStyle) {
                    $sheet->applyStyle($applyStyle);
                }
            }
            if ($rowStyles && isset($rowStyles[$rowNr])) {
                $sheet->setRowStyle($rowNr, $rowStyles[$rowNr]);
            }
            $sheet->nextRow();
        }
        if ($autoFilterRow) {
            $sheet->setAutofilter($autoFilterRow);
        }
    }

    /**
     * Save the excel file to given path
     * @param string $path The path on disk
     * @return bool
     */
    public function save(string $path): bool
    {
        return $this->excel->save($path);
    }

    /**
     * Download the excel file
     * @param string $filename
     * @return never
     */
    public function download(string $filename): never
    {
        $tmpFile = FileUtils::getTmpFolder() . "/download";
        $this->excel->save($tmpFile);
        if (filesize($tmpFile) <= 10) {
            throw new Exception("Excel File is empty - There may be an error in your excel data (Formula, etc...)");
        } else {
            Response::download(
                $tmpFile,
                $filename
            );
        }
    }

}