<?php
// index.php

require_once __DIR__ . '/autoload.php';

use App\Controller\CashController;

$controller = new CashController();
$controller->handleRequest();
