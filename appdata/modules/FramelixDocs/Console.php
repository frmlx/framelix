<?php

namespace Framelix\FramelixDocs;

class Console extends \Framelix\Framelix\Console
{
    /**
     * Called when the application is warmup, during every docker container start
     * Override this function to provide your own update/upgrade path
     * @return int Status Code, 0 = success
     */
    public static function appWarmup(): int
    {
        parent::appWarmup();
        // todo create demo user
        return 0;
    }
}