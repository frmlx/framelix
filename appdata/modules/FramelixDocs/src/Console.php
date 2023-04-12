<?php

namespace Framelix\FramelixDocs;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\FramelixDocs\Storable\SimpleDemoEntry;

use function shuffle;

class Console extends \Framelix\Framelix\Console
{
    /**
     * Called when the application is warmup, during every docker container start
     * Override this function to provide your own update/upgrade path
     * @return int Status Code, 0 = success
     */
    public static function appWarmup(): int
    {
        self::cleanupDemoData();
        return 0;
    }

    /**
     * Delete all demo data and recreate some fresh demo data
     * @return int Status Code, 0 = success
     */
    public static function cleanupDemoData(): int
    {
        if (!\Framelix\Framelix\Config::doesUserConfigFileExist()) {
            return 0;
        }
        // users
        Storable::deleteMultiple(User::getByCondition());
        for ($i = 4; $i <= 25; $i++) {
            $pw = RandomGenerator::getRandomString(5, 10);
            $obj = new  User();
            $obj->email = "user$i@test.local";
            $obj->setPassword($pw);
            $obj->settings = ['pwRaw' => $pw];
            $obj->flagLocked = false;
            $obj->store();
            $obj->addRole('admin');
        }
        // demo data
        Storable::deleteMultiple(SimpleDemoEntry::getByCondition());
        $arr = [];
        for ($i = 4; $i <= 25; $i++) {
            $obj = new  SimpleDemoEntry();
            $obj->name = "I'm the {$i}th";
            $obj->email = "have-$i-an@email.local";
            $obj->lastLogin = rand(0, 1) ? DateTime::create("now - $i days") : null;
            $obj->createTime = DateTime::create("now + $i days");
            $obj->updateTime = DateTime::create("now + $i days + 1 hour");
            $obj->flagActive = rand(0, 1) === 1;
            $obj->logins = rand(0, 99);
            if (rand(0, 1) && $arr) {
                shuffle($arr);
                $obj->referenceEntry = reset($arr);
            }
            $obj->store();
            $arr[] = $obj;
        }
        return 0;
    }
}