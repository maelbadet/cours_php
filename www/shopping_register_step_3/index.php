<?php
// index.php

require_once __DIR__ . '/controller/cashController.php';

$controller = new CashController();
$controller->handleRequest();