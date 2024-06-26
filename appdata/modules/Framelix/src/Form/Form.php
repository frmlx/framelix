<?php

namespace Framelix\Framelix\Form;

use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Html\TypeDefs\ElementColor;
use Framelix\Framelix\Html\TypeDefs\JsRequestOptions;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\View;
use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;

use function array_shift;
use function get_class;
use function is_array;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;

/**
 * Framelix form generator
 */
class Form implements JsonSerializable
{

    /**
     * The id for the form
     * @var string
     */
    public string $id;

    /**
     * The label/title above the form if desired
     * @var string|null
     */
    public ?string $label;

    /**
     * Additional form html attributes
     * @var HtmlAttributes|null
     */
    public ?HtmlAttributes $htmlAttributes = null;

    /**
     * The fields attached to this form
     * @var Field[]
     */
    public array $fields = [];

    /**
     * The buttons attached to the form
     * @var array
     */
    public array $buttons = [];

    /**
     * Submit method (only for non async submits, async is always post)
     * @var string
     */
    #[ExpectedValues(values: ["post", "get"])]
    public string $submitMethod = 'post';

    /**
     * The url to submit to
     * If null then it is the current url or current tag
     * @var Url|View|string|null
     */
    public Url|View|string|null $submitUrl = null;

    /**
     * The request options instead of submitUrl
     * @var JsRequestOptions|array|null
     */
    public JsRequestOptions|array|null $requestOptions = [
        'url' => null,
        'renderTarget' => JsRequestOptions::RENDER_TARGET_CURRENT_CONTEXT,
    ];

    /**
     * Submit the form async
     * If false then the form will be submitted with native form submit features (new page load)
     * @var bool
     */
    public bool $submitAsync = true;

    /**
     * Submit the form async with raw data instead of POST/GET
     * Data can be retreived with Request::getBody()
     * This cannot be used when form contains file uploads
     * @var bool
     */
    public bool $submitAsyncRaw = false;

    /**
     * Validation message to show in the frontend
     * @var string|null
     */
    public string|null $validationMessage = null;

    /**
     * Submit the form with enter key
     * @var bool
     */
    public bool $submitWithEnter = false;

    /**
     * Allow browser autocomplete in this form
     * @var bool
     */
    public bool $autocomplete = false;

    /**
     * Form buttons are sticked to the bottom of the screen and always visible
     * @var bool
     */
    public bool $stickyFormButtons = false;

    /**
     * Group fields
     * @var array|null
     */
    protected ?array $fieldGroups = null;

    /**
     * Make a form read only, effectively forces all fields to be disabled and remove action buttons row
     * @var bool
     */
    public bool $readOnly = false;

    /**
     * Additional html after the form to append
     * @var string|null
     */
    public ?string $appendHtml = null;

    /**
     * Check if the form with the given id is submitted
     * @param string $formId
     * @return bool
     */
    public static function isFormSubmitted(string $formId): bool
    {
        $formId = "framelix-form-" . $formId;
        if (Request::getPost($formId) === '1') {
            return true;
        }
        if (Request::getGet($formId) === '1') {
            return true;
        }
        return false;
    }

    /**
     * Show form
     * @param bool $fillWithSubmittedData If true then fill form data with submitted data, if form isn't async and
     *     current request contains form data
     */
    final public function show(bool $fillWithSubmittedData = true): void
    {
        // if this form is already submitted without async, then validate and fill data before generating it
        if ($fillWithSubmittedData && !$this->submitAsync && self::isFormSubmitted($this->id)) {
            foreach ($this->fields as $field) {
                $field->defaultValue = $field->getSubmittedValue();
            }
            $this->validate(false);
        }
        PhpToJsData::renderToHtml($this->jsonSerialize());
    }

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
     * Get form html attributes
     * @return HtmlAttributes
     */
    public function getHtmlAttributes(): HtmlAttributes
    {
        if ($this->htmlAttributes === null) {
            $this->htmlAttributes = new HtmlAttributes();
        }
        return $this->htmlAttributes;
    }

    /**
     * Add a field
     * @param Field $field
     */
    public function addField(Field $field): void
    {
        $field->form = $this;
        $this->fields[$field->name] = $field;
    }

    /**
     * Remove a field by name
     * @param string $name
     */
    public function removeField(string $name): void
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->form = null;
            unset($this->fields[$name]);
        }
    }

    /**
     * Add a button where you later can bind custom actions
     * @param string $actionId
     * @param string $buttonText
     * @param string|null $buttonIcon
     * @param ElementColor|string $buttonColor
     * @param string|null $buttonTooltip
     * @param bool $ignoreReadOnly If true and $readOnly of form is also true, this button will still be kept visible,
     *    Buttons without this flag will be hidden when the form is readOnly
     * @param HtmlAttributes|null $additionalAttributes Add additional html attributes
     */
    public function addButton(
        string $actionId,
        string $buttonText,
        ?string $buttonIcon = '70c',
        ElementColor|string $buttonColor = ElementColor::THEME_DEFAULT,
        ?string $buttonTooltip = null,
        bool $ignoreReadOnly = false,
        HtmlAttributes|null $additionalAttributes = null
    ): void {
        $this->buttons[] = [
            'type' => 'action',
            'action' => $actionId,
            'color' => $buttonColor,
            'buttonText' => Lang::get($buttonText),
            'buttonIcon' => $buttonIcon,
            'buttonTooltip' => $buttonTooltip ? Lang::get($buttonTooltip) : null,
            'ignoreReadOnly' => $ignoreReadOnly,
            'additionalAttributes' => $additionalAttributes,
        ];
    }

    /**
     * Add a button to load a url
     * @param Url $url
     * @param string $buttonText
     * @param string|null $buttonIcon
     * @param ElementColor|string $buttonColor
     * @param string|null $buttonTooltip
     * @param bool $ignoreReadOnly Same as in self::addButton
     * @param HtmlAttributes|null $additionalAttributes Add additional html attributes
     * @see self::addButton()
     */
    public function addLoadUrlButton(
        Url $url,
        string $buttonText = '__framelix_stop_edit__',
        ?string $buttonIcon = '70c',
        ElementColor|string $buttonColor = ElementColor::THEME_DEFAULT,
        ?string $buttonTooltip = null,
        bool $ignoreReadOnly = false,
        HtmlAttributes|null $additionalAttributes = null
    ): void {
        $this->buttons[] = [
            'type' => 'url',
            'url' => $url->getUrlAsString(),
            'color' => $buttonColor,
            'buttonText' => Lang::get($buttonText),
            'buttonIcon' => $buttonIcon,
            'buttonTooltip' => $buttonTooltip ? Lang::get($buttonTooltip) : null,
            'ignoreReadOnly' => $ignoreReadOnly,
            'additionalAttributes' => $additionalAttributes,
        ];
    }

    /**
     * Add submit button
     * @param string $submitFieldName
     * @param string $buttonText
     * @param string|null $buttonIcon
     * @param ElementColor|string $buttonColor
     * @param string|null $buttonTooltip
     * @param bool $ignoreReadOnly Same as in self::addButton
     * @param HtmlAttributes|null $additionalAttributes Add additional html attributes
     * @see self::addButton()
     */
    public function addSubmitButton(
        string $submitFieldName = 'save',
        string $buttonText = '__framelix_save__',
        ?string $buttonIcon = '718',
        ElementColor|string $buttonColor = ElementColor::THEME_SUCCESS,
        ?string $buttonTooltip = null,
        bool $ignoreReadOnly = false,
        HtmlAttributes|null $additionalAttributes = null
    ): void {
        $this->buttons[] = [
            'type' => 'submit',
            'submitFieldName' => $submitFieldName,
            'color' => $buttonColor,
            'buttonText' => Lang::get($buttonText),
            'buttonIcon' => $buttonIcon,
            'buttonTooltip' => $buttonTooltip ? Lang::get($buttonTooltip) : null,
            'ignoreReadOnly' => $ignoreReadOnly,
            'additionalAttributes' => $additionalAttributes,
        ];
    }

    /**
     * Add a field group
     * Each field in $fieldNames will be grouped under a collapsable container with $label
     * The group collapsable will be inserted before the first field in $fieldNames
     * @param string $id
     * @param string $label
     * @param string[] $fieldNames
     * @param bool $defaultState
     * @param bool $rememberState
     * @return void
     */
    public function addFieldGroup(
        string $id,
        string $label,
        array $fieldNames,
        bool $defaultState = true,
        bool $rememberState = true
    ): void {
        $this->fieldGroups[$id] = [
            'label' => $label,
            'fieldNames' => $fieldNames,
            'defaultState' => $defaultState,
            'rememberState' => $rememberState,
        ];
    }

    /**
     * Remove field groupby given id
     * @param string $id
     * @return void
     */
    public function removeFieldGroup(string $id): void
    {
        if ($this->fieldGroups) {
            unset($this->fieldGroups[$id]);
        }
    }

    /**
     * Get submitted values
     * @return array
     */
    public function getSubmittedValues(): array
    {
        $arr = [];
        foreach ($this->fields as $fieldName => $field) {
            $arr[$fieldName] = $field->getSubmittedValue();
        }
        return $arr;
    }

    /**
     * Get converted submitted values
     * @return array
     */
    public function getConvertedSubmittedValues(): array
    {
        $arr = [];
        foreach ($this->fields as $fieldName => $field) {
            $arr[$fieldName] = $field->getConvertedSubmittedValue();
        }
        return $arr;
    }

    /**
     * Set all storable values that exist as properties with corresponing field names
     * @param Storable $storable
     */
    public function setStorableValues(Storable $storable): void
    {
        foreach ($this->fields as $field) {
            // file fields cannot be handled here
            if ($field instanceof File) {
                continue;
            }
            $fieldValue = $field->getConvertedSubmittedValue();
            $nameParts = ArrayUtils::splitKeyString($field->name);
            $storableSchemaProperty = Storable::getStorableSchemaProperty($storable, $nameParts[0]);
            if (!$storableSchemaProperty) {
                continue;
            }
            // in case of mixed data with raw json, merge arrays keys separately instead of override complete property
            if ($storableSchemaProperty->internalType === 'mixed') {
                array_shift($nameParts);
                $storableValue = $storable->{$storableSchemaProperty->name} ?? [];
                if (!is_array($storableValue)) {
                    $storableValue = [];
                }
                ArrayUtils::setValue($storableValue, $nameParts, $fieldValue);
                $storable->{$storableSchemaProperty->name} = $storableValue;
                continue;
            }
            $storable->{$storableSchemaProperty->name} = $fieldValue;
        }
    }

    /**
     * Set default values of form fields based on array|object
     * @param mixed $data
     * @return void
     */
    public function setDefaultValues(mixed $data): void
    {
        foreach ($this->fields as $field) {
            $value = ArrayUtils::getValue($data, $field->name);
            if ($value !== null) {
                $field->defaultValue = $value;
            }
        }
    }

    /**
     * Run custom store methods from all attached fields that handle field specific storage for fields
     * @param Storable $storable
     */
    public function store(Storable $storable): void
    {
        foreach ($this->fields as $field) {
            $field->store($storable);
        }
    }

    /**
     * Validate the form
     * @param bool $asyncValidation If true and validation messages exist, then output messages as json and stop code
     *     execution
     * @return bool
     */
    public function validate(bool $asyncValidation = true): bool
    {
        $success = true;
        $messages = [];
        foreach ($this->fields as $field) {
            $validation = $field->validate();
            if ($validation !== true) {
                $field->validationMessage = $validation;
                $messages[$field->name] = $validation;
                $success = false;
            }
        }
        if (!$success && $asyncValidation && Request::isAsync()) {
            Response::stopWithFormValidationResponse($messages);
        }
        return $success;
    }

    /**
     * Get json data
     * @return PhpToJsData
     */
    public function jsonSerialize(): PhpToJsData
    {
        $properties = [];
        foreach ($this as $key => $value) {
            if ($key === 'submitUrl' && $value instanceof View) {
                $value = View::getUrl(get_class($value));
            }
            $properties[$key] = $value;
        }
        return new PhpToJsData($properties, $this, 'FramelixForm');
    }

}