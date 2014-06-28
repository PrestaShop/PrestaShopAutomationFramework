<?php
ini_set('display_erorrs', 'on');
require_once __DIR__.'/vendor/autoload.php';

$cli_tool = new PrestaShop\CommandLineTool();
$cli_tool->run();
