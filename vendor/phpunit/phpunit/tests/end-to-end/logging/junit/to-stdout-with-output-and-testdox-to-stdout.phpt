--TEST--
phpunit --log-junit php://stdout --testdox-text php://stdout ../../_files/Success.php
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = '--do-not-record-test-run-history';
$_SERVER['argv'][] = '--no-configuration';
$_SERVER['argv'][] = '--log-junit';
$_SERVER['argv'][] = 'php://stdout';
$_SERVER['argv'][] = '--testdox-text';
$_SERVER['argv'][] = 'php://stdout';
$_SERVER['argv'][] = __DIR__ . '/../../../_files/Success.php';

require_once __DIR__ . '/../../../bootstrap.php';

(new PHPUnit\TextUI\Application)->run($_SERVER['argv']);
--EXPECTF--
PHPUnit %s by Sebastian Bergmann and contributors.

Runtime: %s

.                                                                   1 / 1 (100%)<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="PHPUnit\TestFixture\Success" file="%sSuccess.php" tests="1" assertions="1" errors="0" failures="0" skipped="0" time="%f">
    <testcase name="testOne" file="%sSuccess.php" line="%d" class="PHPUnit\TestFixture\Success" classname="PHPUnit.TestFixture.Success" assertions="1" time="%f"/>
  </testsuite>
</testsuites>
Success (PHPUnit\TestFixture\Success)
 [x] One



Time: %s, Memory: %s

OK (1 test, 1 assertion)
