--TEST--
phpunit --compact ../_files/data-provider/EmptyDatasetTest.php
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = '--do-not-record-test-run-history';
$_SERVER['argv'][] = '--no-configuration';
$_SERVER['argv'][] = '--compact';
$_SERVER['argv'][] = __DIR__ . '/../_files/data-provider/EmptyDatasetTest.php';

require_once __DIR__ . '/../../bootstrap.php';

(new PHPUnit\TextUI\Application)->run($_SERVER['argv']);
--EXPECTF--
PHPUnit %s by Sebastian Bergmann and contributors.

Runtime: %s

No tests executed!

--- PHPUNIT ERROR: PHPUnit\TestFixture\DataProvider\EmptyDatasetTest::testOne
The data provider specified for PHPUnit\TestFixture\DataProvider\EmptyDatasetTest::testOne is invalid
Empty data set provided by data provider

--- PHPUNIT TEST RUNNER WARNING
No tests found in class "PHPUnit\TestFixture\DataProvider\EmptyDatasetTest".
