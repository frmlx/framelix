<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Lang;

use function is_string;
use function preg_match;
use function preg_quote;

/**
 * A email field with email format validation
 */
class Email extends Text
{
    public int|string|null $maxWidth = 400;

    /**
     * Type for this input field
     * @var string
     */
    public string $type = "email";

    /**
     * Validate
     * Return error message on error or true on success
     * @return string|bool
     */
    public function validate(): string|bool
    {
        if (!$this->isVisible()) {
            return true;
        }
        $parentValidation = parent::validate();
        if ($parentValidation !== true) {
            return $parentValidation;
        }
        $value = $this->getSubmittedValue();
        if ($value !== null && strlen($value)) {
            $regex = "~^[a-zA-Z0-9"
                . preg_quote(".!#$%&’*+/=?^_`{|}~-", "~") . "]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-.]{2,}~";
            if (!preg_match($regex, $value)) {
                return Lang::get('__framelix_form_validation_email__');
            }
        }
        return true;
    }
}