<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Utils\StringUtils;

/**
 * A html field. Not a real input field, just to provide a case to integrate any html into a form
 */
class Html extends Field
{
    /**
     * Validate
     * A html field contains nothing to be submitted
     * @return bool
     */
    public function validate(): bool
    {
        return true;
    }

    public function getSubmittedValue(): ?string
    {
        // html field have no values to submit
        return null;
    }

    public function jsonSerialize(): PhpToJsData
    {
        $data = parent::jsonSerialize();
        if (isset($this->defaultValue)) {
            $data->properties['defaultValue'] = StringUtils::stringify(
                $this->defaultValue,
                '<br/>',
                ["getHtmlString"]
            );
        }
        return $data;
    }
}