<?php

namespace Framelix\Framelix\Exception;


use Exception;

/**
 * Throw a fatal error
 * This does log errors in the logs (disk, email, etc...)
 */
class FatalError extends Exception
{

}