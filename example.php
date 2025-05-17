<?php 

require 'vendor/autoload.php';

use ModXengine\Environment\TemplateLoader;
use ModXengine\ModXEngine;
use ModXengine\Cache\TemplateCache;

// $environment = FromArray::templatePath(['templates/']);
$environment = TemplateLoader::templatePath('templates');
$cache = new TemplateCache('cache', 'template', 3600);

$engine = new ModXEngine($environment, $cache);
$engine->set('food', 'yam')
    ->set('title', 'testing')
    ->set('user', 'ile,')
    ->with([
        'test_data' => ['ssssss', 'ssssss'],
        'array_test' => ['1', 2, 'testng']
    ])->layout("main");

echo $engine->render('text', 10);
