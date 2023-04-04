<?php

$coverage = "Failed";
$cloverXmlPath = "/framelix/userdata/clover.xml";
$cloverCoveragePath = "/framelix/userdata/clover-coverage.txt";
if (file_exists($cloverXmlPath)) {
    $xml = new SimpleXMLElement($cloverXmlPath, 0, true);
    $reportMetrics = $xml->xpath('project/metrics')[0] ?? null;
    $metricsAttributes = $reportMetrics->attributes();
    $elements = (int)($metricsAttributes->elements ?? 0);
    $coveredElements = (int)($metricsAttributes->coveredelements ?? 0);
    $elements = $elements === 0 ? 1 : $elements;
    $coverage = round($coveredElements / $elements * 100);
}
file_put_contents($cloverCoveragePath, "$coverage%");