<?php

require_once __DIR__ . '/config.php';

$testSuite = new \Molengo\TestSuite();
$testSuite->setTestDir(__DIR__ . '/../src/Molengo/Test');
$testSuite->setTemplate(__DIR__ . '/html/test.html.php');
$testSuite->setCoverage(!empty($_GET['coverage']));
$testSuite->setCoverageDir(__DIR__ . '/coverage');
$testSuite->setNamespace('\Molengo\\Test\\');
$testSuite->run();
exit;
