<?php

$msg = 'Error';
$color = '#FF2100';
$msgFile = '/framelix/userdata/playwright/badge-message.txt';
$colorFile = '/framelix/userdata/playwright/badge-color.txt';
$coverage = "Failed";
$resultsFile = "/framelix/userdata/playwright/results/results.json";
if (file_exists($resultsFile)) {
    $jsonData = json_decode(file_get_contents($resultsFile), true);
    if (is_array($jsonData['errors'] ?? null) && !$jsonData['errors']) {
        $tests = 0;
        foreach ($jsonData['suites'] ?? [] as $suite) {
            foreach ($suite['specs'] ?? [] as $spec) {
                $tests += count($spec['tests']);
            }
        }
        $msg = 'Passed ' . $tests . ' Tests';
        $color = '#0fbb75';
    }
}
file_put_contents($msgFile, $msg);
file_put_contents($colorFile, $color);