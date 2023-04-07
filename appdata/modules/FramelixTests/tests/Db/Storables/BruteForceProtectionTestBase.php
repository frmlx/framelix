<?php

namespace Db\Storables;

use Framelix\Framelix\Config;
use Framelix\Framelix\Storable\BruteForceProtection;
use Framelix\FramelixTests\TestCaseDbTypes;

use function sleep;

abstract class BruteForceProtectionTestBase extends TestCaseDbTypes
{
    public function test(): void
    {
        $this->setupDatabase();
        Config::$clientIpOverride = '127.0.0.1';
        BruteForceProtection::reset('testid1', 'test');
        $this->assertFalse(BruteForceProtection::isBlocked('testid1', false, 1, 1, 'test'));
        BruteForceProtection::countUp('testid1', 'test');
        BruteForceProtection::countUp('testid1', 'test');
        // after reaching threshold, it is blocked until enough time has passed
        // in this test we use minimal time possible
        $this->assertTrue(BruteForceProtection::isBlocked('testid1', true, 1, 1, 'test'));
        $this->assertToastError();
        sleep(1);
        $this->assertFalse(BruteForceProtection::isBlocked('testid1', false, 1, 1, 'test'));

        BruteForceProtection::countUp('testid1', 'test');
        $this->assertTrue(BruteForceProtection::isBlocked('testid1', false, 1, 1, 'test'));
        sleep(2);
        // still blocked, must wait 2 seconds because 2 counts already exist
        $this->assertTrue(BruteForceProtection::isBlocked('testid1', false, 1, 2, 'test'));
        sleep(2);
        $this->assertFalse(BruteForceProtection::isBlocked('testid1', false, 1, 1, 'test'));
        // count up block again
        BruteForceProtection::countUp('testid1', 'test');
        $this->assertTrue(BruteForceProtection::isBlocked('testid1', false, 1, 1, 'test'));
        // do the same check but choose a higher treshold which is not blocked
        $this->assertFalse(BruteForceProtection::isBlocked('testid1', false, 5, 1, 'test'));
        BruteForceProtection::reset('testid1', 'test');
        $this->assertFalse(BruteForceProtection::isBlocked('testid1', false, 1, 1, 'test'));
    }
}