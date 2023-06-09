<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\LazySearchCondition;
use Framelix\Framelix\Db\StorableSchemaProperty;
use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Storable\SystemValue;
use Framelix\Framelix\Utils\ArrayUtils;

use function class_exists;
use function get_class;
use function is_array;
use function is_object;
use function str_replace;

/**
 * Storable meta property
 */
class StorableMetaProperty
{
    /**
     * The parent meta name
     * @var StorableMeta
     */
    public StorableMeta $meta;

    /**
     * The property name
     * @var string
     */
    public string $name;

    /**
     * The form field assigned to this property
     * @var Field|null
     */
    public ?Field $field = null;

    /**
     * A callable to get the value for any context
     * @var callable|null
     */
    public $valueCallable = null;

    /**
     * Define columns for this single property in the lazy search condition, instead of the default.
     * Default is the property name
     * If you want to search deeper, example a user property in createUser, then use: "createUser.email"
     * @var LazySearchCondition
     */
    public LazySearchCondition $lazySearchConditionColumns;

    /**
     * Table row default cell attributes to use
     * @var HtmlAttributes
     */
    public HtmlAttributes $tableCellDefaultAttributes;

    /**
     * The labels for all contexts
     * @var string[]
     */
    private array $labels = [];

    /**
     * The label descriptions for all contexts
     * @var mixed[][]
     */
    private array $labelDescriptions = [];

    /**
     * Context visibilities
     * @var bool[]
     */
    private array $visibilities = [
        StorableMeta::CONTEXT_TABLE => true,
        StorableMeta::CONTEXT_FORM => true,
        StorableMeta::CONTEXT_QUICK_SEARCH => true,
        StorableMeta::CONTEXT_EXTENDED_SEARCH => true
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tableCellDefaultAttributes = new HtmlAttributes();
        $this->lazySearchConditionColumns = new LazySearchCondition();
    }

    /**
     * Add a default field for this property, based on the storables property schema
     * If this property has not storable property schema, then nothing is added
     * @return Field|null
     */
    public function addDefaultField(): ?Field
    {
        $storableSchemaProperty = Storable::getStorableSchemaProperty($this->meta->storable, $this->name);
        if (!$storableSchemaProperty) {
            return null;
        }
        $field = new Field\Text();
        if ($storableSchemaProperty->storableClass) {
            $storableClass = new $storableSchemaProperty->storableClass();
            if ($storableClass instanceof SystemValue) {
                $entries = $storableClass::getEntries($this->getValue());
                $field = new Field\Select();
                $field->addOptionsByStorables($entries);
                if (count($entries) > 10) {
                    $field->searchable = true;
                }
            } elseif ($storableClass instanceof StorableFile) {
                $entries = $storableClass::getByCondition('assignedStorable = {0}', [$this->meta->storable]);
                $field = new Field\File();
                $field->storableFileBase = $storableClass;
                $field->defaultValue = $entries;
            } elseif ($storableClass instanceof Storable) {
                $class = get_class($storableClass);
                $storableMetaClass = str_replace("\\Storable\\", "\\StorableMeta\\", $class);
                if (class_exists($storableMetaClass)) {
                    $field = new Field\Search();
                    $field->setSearchWithStorableMetaQuickSearch($class, $storableMetaClass);
                }
            }
        } elseif ($storableSchemaProperty->storableInterface) {
            $storableClass = new $storableSchemaProperty->storableInterface();
            if ($storableClass instanceof DateTime) {
                $field = new Field\Date();
            } elseif ($storableClass instanceof Date) {
                $field = new Field\Date();
            } elseif ($storableClass instanceof Time) {
                $field = new Field\Time();
            }
        } else {
            switch ($storableSchemaProperty->internalType) {
                case 'bool':
                    $field = new Field\Toggle();
                    break;
                case 'int':
                case 'float':
                    $field = new Field\Number();
                    break;
                case 'string':
                    if ($storableSchemaProperty->length) {
                        $field = new Field\Text();
                    } else {
                        $field = new Field\Textarea();
                    }
                    break;
            }
        }
        $field->setFieldOptionsForStorable($this->meta->storable, $this->name);
        $this->field = $field;
        return $field;
    }

    /**
     * Get a field that is prepared for current metas context
     * Does clone the original field and modify it to the context needs
     * @return Field|null
     */
    public function getField(): ?Field
    {
        if (!$this->field || !$this->visibilities[$this->meta->context]) {
            return null;
        }
        $field = clone $this->field;
        $field->name = $this->name;
        $field->label = $this->getLabel();
        $field->labelDescription = $this->getLabelDescription();
        $storableSchemaProperty = $this->getSchemaProperty();
        $field->defaultValue = $this->getValue();
        if ($storableSchemaProperty) {
            $field->setFieldOptionsForStorable($this->meta->storable, $storableSchemaProperty->name);
        }
        return $field;
    }

    /**
     * Set label for a specific context
     * @param string $label
     * @param int|null $context If null, than the same label is used everywhere, otherwise this label only is valid for given context
     */
    public function setLabel(string $label, ?int $context = null): void
    {
        $this->labels[(int)$context] = $label;
    }

    /**
     * Get label for current meta context
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->labels[$this->meta->context] ?? $this->labels[0] ?? null;
    }

    /**
     * Set label description
     * @param string $description
     * @param bool $tooltip If true, than description is only visible with a tooltip
     * @param int|null $context If null, than the same label is used everywhere, otherwise this label only is valid for given context
     */
    public function setLabelDescription(string $description, bool $tooltip = false, ?int $context = null): void
    {
        $this->labelDescriptions[(int)$context] = ['description' => $description, "tooltip" => $tooltip];
    }

    /**
     * Get label description for current meta context
     * @return string|null
     */
    public function getLabelDescription(): ?string
    {
        return $this->labelDescriptions[$this->meta->context]['description'] ?? $this->labelDescriptions[0]['description'] ?? null;
    }

    /**
     * Set visibility for current meta context
     * @param int|int[]|null $context self::CONTEXT_* Multiple with an array, null means all visibilities at once
     * @param bool $visibility
     */
    public function setVisibility(int|array|null $context, bool $visibility): void
    {
        if ($context === null) {
            foreach ($this->visibilities as $key => $visibilityExisting) {
                $this->visibilities[$key] = $visibility;
            }
            return;
        }
        if (is_array($context)) {
            foreach ($context as $contextSingle) {
                $this->visibilities[$contextSingle] = $visibility;
            }
            return;
        }
        $this->visibilities[$context] = $visibility;
    }

    /**
     * Get visibility for current meta context
     * @return bool
     */
    public function getVisibility(): bool
    {
        return $this->visibilities[$this->meta->context] ?? false;
    }

    /**
     * Get a value for current metas context
     * @param bool $skipValueCallable If true do not use valueCallable, even if it is defined
     * @return mixed
     */
    public function getValue(bool $skipValueCallable = false): mixed
    {
        if (!$skipValueCallable && $this->valueCallable) {
            return call_user_func($this->valueCallable);
        }
        $storableSchemaProperty = $this->getSchemaProperty();
        if (!$storableSchemaProperty) {
            return null;
        }
        $value = $this->meta->storable->{$storableSchemaProperty->name};
        $nameParts = ArrayUtils::splitKeyString($this->name);
        if (count($nameParts) > 1) {
            unset($nameParts[0]);
            $value = ArrayUtils::getValue($value, $nameParts);
        }
        if ($value !== null && $this->meta->context !== StorableMeta::CONTEXT_FORM && $this->field) {
            $fieldClass = get_class($this->field);
            if ($this->field instanceof Field\Select && $fieldClass === Field\Select::class) {
                if (is_array($value)) {
                    foreach ($value as $key => $v) {
                        $value[$key] = Lang::get($this->field->getOptionLabel($v) ?? $v);
                    }
                } elseif (!is_object($value)) {
                    $value = Lang::get($this->field->getOptionLabel($value) ?? $value);
                }
            }
        }
        return $value;
    }

    /**
     * Get schema  property for metas storable
     * @return StorableSchemaProperty|null
     */
    public function getSchemaProperty(): ?StorableSchemaProperty
    {
        $nameParts = ArrayUtils::splitKeyString($this->name);
        return Storable::getStorableSchemaProperty($this->meta->storable, $nameParts[0]);
    }
}