<?php

namespace Framelix\Framelix\Exception;

use Exception;

/**
 * Soft error
 * It is used to stop script execution gracefully to be able to use it even in unit tests
 * It does not log any error log for this
 */
class SoftError extends Exception
{
}