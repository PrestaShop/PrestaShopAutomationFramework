<?php
ini_set('display_errors', 'on');
require_once __DIR__.'/vendor/autoload.php';

$cli_tool = new PrestaShop\PSTAF\CommandLineTool();
$cli_tool->run();
