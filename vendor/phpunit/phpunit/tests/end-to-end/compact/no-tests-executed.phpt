--TEST--
phpunit --compact --filter doesNotMatch ../../_files/Success.php
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = '--do-not-record-test-run-history';
$_SERVER['argv'][] = '--no-configuration';
$_SERVER['argv'][] = '--compact';
$_SERVER['argv'][] = '--filter';
$_SERVER['argv'][] = 'doesNotMatch';
$_SERVER['argv'][] = __DIR__ . '/../../_files/Success.php';

require_once __DIR__ . '/../../bootstrap.php';

(new PHPUnit\TextUI\Application)->run($_SERVER['argv']);
--EXPECTF--
PHPUnit %s by Sebastian Bergmann and contributors.

Runtime: %s

No tests executed!
