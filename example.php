<?php 

require 'vendor/autoload.php';

use ModXengine\View;
use ModXengine\ModXEngine;
use ModXengine\Cache\TemplateCache;

// $environment = TemplateLoader::templatePath('templates');
// $cache = new TemplateCache('cache', 'template', 3600);

// $engine = new ModXEngine($environment, $cache);
// $engine->set('food', 'yam')
//     ->set('title', 'testing')
//     ->set('user', 'ile,')
//     ->with([
//         'test_data' => ['ssssss', 'ssssss'],
//         'array_test' => ['1', 2, 'testng']
//     ])->layout("main");

// echo $engine->render('text', 10);
//  You Can Make use of The View Class

use ModXengine\Environment\FromArray;


// Initialize environment
$environment = FromArray::templatePath(
    ['templates/'], // Template directories
    __DIR__, // Root path
    true // Create directories if missing
);

// Initialize View
$view = new View(
    environment: $environment,
    cacheDir: __DIR__ . '/cache/',
    layoutDir: 'layouts',
    componentDir: 'components'
);

// Render template
//The with method is used to set multiple data as array while the set method is used to set single data based on key as the first variable and the value as the second
$output = $view
->set('title', 'testing')
    ->set('user', 'ile,')
    ->with([
        'test_data' => ['ssssss', 'ssssss'],
        'array_test' => ['1', 2, 'testng']
    ])->layout("main");; // Cache for 1 hour

echo $output->render('text', 200);
