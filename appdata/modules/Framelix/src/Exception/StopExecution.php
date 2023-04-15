<?php

namespace Framelix\Framelix\Exception;

use Exception;

/**
 * Stops script execution (Graceful alternative to die() or exit())
 * It is used to stop script execution gracefully to be able to use it even in unit tests
 * It does not log any errors
 */
class StopExecution extends Exception
{
}