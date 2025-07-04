<?php

namespace Db\Storables;

use Framelix\Framelix\Config;
use Framelix\Framelix\Storable\BruteForceProtection;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class BruteForceProtectionTestBase extends TestCaseDbTypes
{

    public function test(): void
    {
        $this->setupDatabase();
        Config::$clientIpOverride = '127.0.0.1';

        $maxAttempts = 2;
        $timeRangeSpan = 60;
        $mustWaitSecondsIfBlocked = 60;

        BruteForceProtection::reset('testid1', 'test');
        $this->assertFalse(BruteForceProtection::isBlocked('testid1', false, $maxAttempts, $timeRangeSpan, $mustWaitSecondsIfBlocked, connectionId: 'test'));

        BruteForceProtection::logAttempt('testid1', connectionId: 'test');
        $this->assertFalse(BruteForceProtection::isBlocked('testid1', false, $maxAttempts, $timeRangeSpan, $mustWaitSecondsIfBlocked, connectionId: 'test'));

        BruteForceProtection::logAttempt('testid1', connectionId: 'test');
        $this->assertTrue(BruteForceProtection::isBlocked('testid1', true, $maxAttempts, $timeRangeSpan, $mustWaitSecondsIfBlocked, connectionId: 'test'));

        $this->assertToastError();

        $this->assertTrue(
            BruteForceProtection::isBlocked('testid1', true, $maxAttempts, $timeRangeSpan, $mustWaitSecondsIfBlocked, "now + 50 seconds", connectionId: 'test')
        );

        // timespan is 60 seconds, we simulate that 65 seconds has passed, so its valid again
        $this->assertFalse(
            BruteForceProtection::isBlocked('testid1', true, $maxAttempts, $timeRangeSpan, $mustWaitSecondsIfBlocked, "now + 65 seconds", connectionId: 'test')
        );

        // after reset it should work out of the box
        BruteForceProtection::reset('testid1', 'test');
        $this->assertFalse(BruteForceProtection::isBlocked('testid1', true, $maxAttempts, $timeRangeSpan, $mustWaitSecondsIfBlocked, connectionId: 'test'));
    }

}