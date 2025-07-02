<?php

namespace Framelix\Framelix\Form;

use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\NumberUtils;
use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;

use function call_user_func_array;
use function in_array;
use function is_array;
use function is_string;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function preg_match;
use function preg_quote;
use function property_exists;
use function str_contains;
use function strlen;

/**
 * The base for every field in a form
 */
abstract class Field implements JsonSerializable
{

    /**
     * Hide the field completely
     * Does layout jumps but hidden fields take no space
     * @var string
     */
    public const string VISIBILITY_HIDDEN = 'hidden';

    /**
     * Hide the field almost transparent
     * Prevent a lot of layout jumps but hidden fields will take the space
     * @var string
     */
    public const  string VISIBILITY_TRANSPARENT = 'transparent';

    /**
     * The form the field is attached to
     * @var Form|null
     */
    public ?Form $form = null;

    /**
     * Name of the field
     * @var string
     */
    public string $name;

    /**
     * Label
     * @var string|null
     */
    public ?string $label = null;

    /**
     * Label description
     * @var string|null
     */
    public ?string $labelDescription = null;

    /**
     * Minimal width in pixel or other unit
     * Int is considered pixel, string is passed as is
     * @var int|string|null
     */
    public int|string|null $minWidth = null;

    /**
     * Max width in pixel or other css unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = null;

    /**
     * The default value for this field
     * @var mixed|null
     */
    public mixed $defaultValue = null;

    /**
     * Is the field disabled
     * @var bool
     */
    public bool $disabled = false;

    /**
     * Is the field required
     * @var bool
     */
    public bool $required = false;

    /**
     * Validation message to show in the frontend
     * @var string|array|null
     */
    public string|array|null $validationMessage = null;

    /**
     * Define how hidden fields should be hidden
     * @var string
     */
    public string $visibilityConditionHideMethod = Field::VISIBILITY_HIDDEN;

    /**
     * A condition to define when this field is visible
     * Hidden fields will not be validated
     * @var FieldVisibilityCondition|null
     */
    protected ?FieldVisibilityCondition $visibilityCondition = null;

    /**
     * Custom Converter
     * Receives 2 parameters
     * @var callable|null
     */
    protected $converter;

    /**
     * Custom validator
     * Must return true on success and string on error
     * Receives 2 parameters
     * @var callable|null
     */
    protected $validator;

    /**
     * The fields position in form
     * @var array|null
     */
    protected ?array $positionInForm = null;

    /**
     * Get html
     * @return string
     */
    final public function getHtml(): string
    {
        ob_start();
        $this->show();
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Show field
     */
    final public function show(): void
    {
        PhpToJsData::renderToHtml(
            $this->jsonSerialize(),
            HtmlAttributes::create(classes: ["framelix-form-field-single"])
        );
    }

    /**
     * Get submitted value
     * @return array|string|null
     */
    public function getSubmittedValue(): array|string|null
    {
        if ($_POST) {
            return Request::getPost($this->name);
        }
        return Request::getGet($this->name);
    }

    /**
     * Get converted submitted value
     * @return mixed
     */
    final public function getConvertedSubmittedValue(): mixed
    {
        if ($this->converter) {
            $value = call_user_func_array(
                $this->converter,
                [$this->getSubmittedValue(), $this->getDefaultConvertedSubmittedValue()]
            );
        } else {
            $value = $this->getDefaultConvertedSubmittedValue();
        }
        return $value;
    }

    /**
     * Set converter to storable
     * @param string|null $storableClass
     */
    public function setConverterStorable(?string $storableClass = null): void
    {
        if (!$storableClass) {
            $this->converter = null;
            return;
        }
        $this->converter = function (mixed $submittedValue) use (/** @var Storable $storableClass */ $storableClass) {
            if (is_array($submittedValue)) {
                return $storableClass::getByIds($submittedValue) ?: null;
            }
            if (is_string($submittedValue)) {
                return $storableClass::getById($submittedValue);
            }
            return null;
        };
    }

    /**
     * Set converter to storable interface
     * @param string|null $storableClass
     */
    public function setConverterStorableInterface(?string $storableClass = null): void
    {
        if (!$storableClass) {
            $this->converter = null;
            return;
        }
        $this->converter = function (mixed $submittedValue) use ($storableClass) {
            if (is_array($submittedValue)) {
                $arr = [];
                foreach ($submittedValue as $key => $value) {
                    $value = call_user_func_array([$storableClass, "createFromFormValue"], [$value]) ?: null;
                    if ($value !== null) {
                        $arr[$key] = $value;
                    }
                }
                if (!$arr) {
                    return null;
                }
                return $arr;
            }
            if (is_string($submittedValue)) {
                return call_user_func_array([$storableClass, "createFromFormValue"], [$submittedValue]) ?: null;
            }
            return null;
        };
    }

    /**
     * Set converter to php type cast
     * @param string|null $castToType
     * @param bool $emptyToNull If value is empty, return null instead of casted value
     */
    public function setConverterCast(?string $castToType = null, bool $emptyToNull = true): void
    {
        if (!$castToType) {
            $this->converter = null;
            return;
        }
        $this->converter = function (mixed $submittedValue, mixed $defaultConvertedValue) use (
            $castToType,
            $emptyToNull
        ) {
            if (!is_string($submittedValue) && $emptyToNull) {
                return null;
            }
            return match ($castToType) {
                "bool" => (bool)$defaultConvertedValue,
                "int" => (int)$defaultConvertedValue,
                "float" => (float)$defaultConvertedValue,
                "string" => (string)$defaultConvertedValue,
                default => $defaultConvertedValue
            };
        };
    }

    /**
     * Add default field options, validators and converters for the given storable property to this field
     * @param Storable $storable
     * @param string $propertyName
     */
    public function setFieldOptionsForStorable(Storable $storable, string $propertyName): void
    {
        $schemaProperty = Storable::getStorableSchemaProperty($storable, $propertyName);
        if (!$schemaProperty) {
            return;
        }
        if ($schemaProperty->length > 0) {
            /** @phpstan-ignore-next-line */
            if (property_exists($this, "maxLength")) {
                $this->maxLength = $schemaProperty->length + ($schemaProperty->decimals > 0 ? $schemaProperty->decimals + 1 : 0);
                /** @phpstan-ignore-next-line */
                if ($this instanceof Number) {
                    $this->maxWidth = $this->maxLength * 10;
                    $this->maxWidth += 20;
                }
            }
            if ($this instanceof Number && $schemaProperty->decimals > 0) {
                $this->decimals = (int)$schemaProperty->decimals;
            }
        }
        if (!$schemaProperty->optional && $schemaProperty->internalType !== 'bool') {
            $this->required = true;
        }
        if (in_array($schemaProperty->internalType, ['bool', 'int', 'float'])) {
            $this->setConverterCast($schemaProperty->internalType, $schemaProperty->optional);
        }
        if ($schemaProperty->storableClass) {
            $this->setConverterStorable($schemaProperty->storableClass);
        }
        if ($schemaProperty->storableInterface) {
            $this->setConverterStorableInterface($schemaProperty->storableInterface);
        }
    }

    /**
     * Has field a visibility condition
     * @return bool
     */
    public function hasVisibilityCondition(): bool
    {
        if ($this->visibilityCondition->data ?? null) {
            return true;
        }
        return false;
    }

    /**
     * Get fields visibility condition
     * @return FieldVisibilityCondition
     */
    public function getVisibilityCondition(): FieldVisibilityCondition
    {
        if (!$this->visibilityCondition) {
            $this->visibilityCondition = new FieldVisibilityCondition();
        }
        return $this->visibilityCondition;
    }

    /**
     * Validate
     * Return error message on error or true on success
     * @return mixed
     */
    public function validate(): mixed
    {
        if (!$this->isVisible()) {
            return true;
        }
        $value = $this->getConvertedSubmittedValue();
        if ($this->validator) {
            $validationMessage = call_user_func_array($this->validator, [$this->getSubmittedValue(), $value]);
            if ($validationMessage !== true) {
                return (string)$validationMessage;
            }
        }
        if ($this->required) {
            if ($value === null
                || $value === false
                || (is_array($value) && !$value)
                || (is_string($value) && !strlen($value))
            ) {
                return Lang::get('__framelix_form_validation_required__');
            }
        }
        return true;
    }

    /**
     * Check if this field is visible in the frontend depending on the visibility condition
     * @return bool
     */
    public function isVisible(): bool
    {
        if (!($this->visibilityCondition->data ?? null)) {
            return true;
        }
        $isVisible = false;
        $submittedValues = $this->form?->getSubmittedValues();
        foreach ($this->visibilityCondition->data as $row) {
            if ($row['type'] === 'or') {
                if ($isVisible) {
                    return true;
                }
                continue;
            }
            if ($row['type'] === 'and') {
                if (!$isVisible) {
                    return false;
                }
                continue;
            }
            $submittedValue = ArrayUtils::getValue($submittedValues, $row['field']);
            $requiredValue = $row['value'] ?? null;
            switch ($row['type']) {
                case 'equal':
                case 'notEqual':
                case 'like':
                case 'notLike':
                    if (!is_array($submittedValue)) {
                        $submittedValue = [$submittedValue];
                    }
                    if (!is_array($requiredValue)) {
                        $requiredValue = [$requiredValue];
                    }
                    foreach ($requiredValue as $requiredValueEntry) {
                        $requiredValueEntry = (string)$requiredValueEntry;
                        if ($row['type'] === 'equal' || $row['type'] === 'like') {
                            foreach ($submittedValue as $submittedValueEntry) {
                                if ($row['type'] === 'equal') {
                                    $isVisible = $submittedValueEntry === $requiredValueEntry;
                                } else {
                                    $isVisible = !!preg_match(
                                        "~" . preg_quote($requiredValueEntry, "~") . "~i",
                                        $submittedValueEntry
                                    );
                                }
                                if ($isVisible) {
                                    continue 4;
                                }
                            }
                        } else {
                            foreach ($submittedValue as $submittedValueEntry) {
                                if ($row['type'] === 'notEqual') {
                                    $isVisible = $submittedValueEntry !== $requiredValueEntry;
                                } else {
                                    $isVisible = !preg_match(
                                        "~" . preg_quote($requiredValueEntry, "~") . "~i",
                                        $submittedValueEntry
                                    );
                                }
                                if ($isVisible) {
                                    continue 4;
                                }
                            }
                        }
                    }
                    break;
                case 'greatherThan':
                case 'greatherThanEqual':
                case 'lowerThan':
                case 'lowerThanEqual':
                    if (is_array($submittedValue)) {
                        $submittedValue = count($submittedValue);
                    } else {
                        $submittedValue = NumberUtils::toFloat($submittedValue);
                    }
                    if ($row['type'] === 'greatherThan') {
                        $isVisible = $submittedValue > $requiredValue;
                    } elseif ($row['type'] === 'greatherThanEqual') {
                        $isVisible = $submittedValue >= $requiredValue;
                    } elseif ($row['type'] === 'lowerThan') {
                        $isVisible = $submittedValue < $requiredValue;
                    } elseif ($row['type'] === 'lowerThanEqual') {
                        $isVisible = $submittedValue <= $requiredValue;
                    }
                    break;
                case 'empty':
                case 'notEmpty':
                    $isVisible = $submittedValue === null || (is_array($submittedValue) && !$submittedValue) || !strlen(
                            $submittedValue
                        );
                    if ($row['type'] === 'notEmpty') {
                        $isVisible = !$isVisible;
                    }
                    break;
            }
        }
        return $isVisible;
    }

    /**
     * Set position of this field in the current
     * Default is one field per row
     * @param string|Field|null $after Attach after the field with the given name, null to reset to defaults
     * @param string|null $sizing
     *  'flow' = each field just sticks to the end of a field, no gap inbetween, a flow
     *  'exact' = size the fields exactly based on css grid grow attributes
     * @param int|null $columnGrowMe Set grid grow size of this field, default is equal size of all fields in a row
     * @param int|null $columnGrowOther Set grid grow size of the others field, default is equal size of all fields in a row
     * @return void
     */
    public function setPositionInForm(
        string|Field|null $after = null,
        #[ExpectedValues(values: ['flow', 'exact'])] ?string $sizing = null,
        ?int $columnGrowMe = null,
        ?int $columnGrowOther = null
    ): void {
        if ($after === null) {
            $this->positionInForm = null;
            return;
        }
        if ($after instanceof Field) {
            $after = $after->name;
        }
        $this->positionInForm = [
            'after' => $after,
            'sizing' => $sizing,
            'columnGrowMe' => $columnGrowMe,
            'columnGrowOther' => $columnGrowOther
        ];
    }

    /**
     * Get json data
     * @return PhpToJsData
     */
    public function jsonSerialize(): PhpToJsData
    {
        $properties = (array)$this;
        foreach ($properties as $key => $property) {
            if (str_contains($key, "*")) {
                unset($properties[$key]);
            }
        }
        $properties['visibilityCondition'] = $this->visibilityCondition;
        $properties['positionInForm'] = $this->positionInForm;
        unset($properties['form']);
        return new PhpToJsData($properties, $this, 'FramelixFormField');
    }

    /**
     * A custom store method that handle field specific storage for storable
     * @param Storable $storable
     * @return mixed
     */
    public function store(Storable $storable): mixed
    {
        return null;
    }

    /**
     * Get default converted submitted value
     * @return mixed
     */
    protected function getDefaultConvertedSubmittedValue(): mixed
    {
        return $this->getSubmittedValue();
    }
}