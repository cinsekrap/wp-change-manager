--TEST--
phpunit --configuration ../_files/baseline/generate-baseline-issue-triggered-more-than-once/phpunit.xml --generate-baseline
--FILE--
<?php declare(strict_types=1);
$baseline = __DIR__ . '/../_files/baseline/generate-baseline-issue-triggered-more-than-once/baseline.xml';
@touch($baseline);
$baseline = realpath($baseline);
@unlink($baseline);

$_SERVER['argv'][] = '--do-not-record-test-run-history';
$_SERVER['argv'][] = '--generate-baseline';
$_SERVER['argv'][] = $baseline;
$_SERVER['argv'][] = '--configuration';
$_SERVER['argv'][] = __DIR__ . '/../_files/baseline/generate-baseline-issue-triggered-more-than-once/phpunit.xml';

require_once __DIR__ . '/../../bootstrap.php';
(new PHPUnit\TextUI\Application)->run($_SERVER['argv']);

print file_get_contents($baseline);

@unlink($baseline);
--EXPECTF--
PHPUnit %s by Sebastian Bergmann and contributors.

Runtime: %s
Configuration: %s

DDDD                                                                4 / 4 (100%)

Time: %s, Memory: %s

OK, but there were issues!
Tests: 4, Assertions: 4, Deprecations: 2.

Baseline written to %sbaseline.xml.
<?xml version="1.0"?>
<files version="1">
 <file path="src/Source.php">
  <line number="19" hash="%s">
   <issue><![CDATA[deprecation]]></issue>
  </line>
  <line number="24" hash="%s">
   <issue><![CDATA[deprecation]]></issue>
  </line>
 </file>
</files>
