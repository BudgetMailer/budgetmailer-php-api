<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

require_once __DIR__ . '/AutoloaderPsr4.php';

define('PHP_BM_ROOT', realpath(__DIR__ . '/..') . '/');

$loader = new AutoloaderPsr4;
$loader->register();
$loader->addNamespace('BudgetMailer\Api', PHP_BM_ROOT . 'src/BudgetMailer/Api');
