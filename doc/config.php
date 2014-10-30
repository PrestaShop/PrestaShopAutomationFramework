<?php

use Sami\Sami;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'src')
    ->in(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'FunctionalTest')->exclude('UpgradeTest')
;

return new Sami($iterator, array(
    'title'                => 'PrestaShopAutomationFramework API',
    'build_dir'            => __DIR__.DIRECTORY_SEPARATOR.'html',
    'cache_dir'            => __DIR__.DIRECTORY_SEPARATOR.'cache',
    'default_opened_level' => 2,
));