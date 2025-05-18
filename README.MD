# ModXEngine Template System
ModXEngine is a lightweight PHP template engine for rendering dynamic templates with support for layouts, components, loops, and caching. It uses a <:directive:> syntax and a modular parser-based architecture.

## Features

### Custom Template Syntax: 
Supports variables, loops, components, and layouts.

### Layouts and Components: 
Enables reusable layouts and modular components.

### Caching: 
Uses Symfony's FilesystemAdapter for efficient template caching.

### Flexible Path Management: 
Supports multiple template directories via FromArray or TemplateLoader.

### Customizable Directories: 
Configurable layout and component folder names.

### Safe Output: 
Automatically escapes variables to prevent XSS.

## Requirements

PHP 7.4 or higher
Composer
Symfony Filesystem and Cache components

## Installation
Clone or Download
git clone <repository-url>
cd modxengine


## Install Dependencies
composer require symfony/filesystem symfony/cache


Set Up DirectoriesCreate:

templates/: For .modx template files
templates/layouts/: For layout files
templates/components/: For component files
cache/: For cache storage (ensure writable)



## Usage
Basic Example
```php
<?php
require 'vendor/autoload.php';

use ModXengine\View;
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
    ->with(['Apple'=>'red', 'Banana'=>'yellow', 'Orange'=>'orange'])
    ->set('user', 'ilem')
    ->layout('main')
    ->render('text', 3600); // Cache for 1 hour
 // Cache for 1 hour

echo $output;
```


Alternative: Using TemplateLoader
```php
use ModXengine\Environment\TemplateLoader;

$environment = TemplateLoader::templatePath(
    'templates/',
    __DIR__,
    true
);

$view = new View($environment, __DIR__ . '/cache/');
```


```php
//using the engine class in place of the view class
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
```
Template Syntax
```html
Variables: <:variable:>
<p>Welcome, <:username:>!</p>
```

Loops: 
```
<:for item, index in array:> ... <:endfor:>
<ul>
    <:for fruit, index in fruits:>
        <li><:index:>: <:fruit:></li>
    <:endfor:>
</ul>
```

Layouts: 
```
<:layout "layout_name":>
<:layout "main":>
<h1>My Page</h1>
```

Components: 
```html
<:component "component_name">
<:component "header" >

<!-- 
Data is passed into the component via the set method, data passed generally into the template is available to all the components of the template
-->
```

Layout Content Placeholder: 
```html
<:content:>
<!-- layouts/main.modx -->
<html>
    <body>
        <:content:>
    </body>
</html>

```
### Comments
comment is added using the # sign and must end with the # sign
```
# comment goes here #
```

Environment Configuration

FromArray: For multiple template directories.
```php
$environment = FromArray::templatePath(['templates/', 'other_templates/'], __DIR__);
```

TemplateLoader: For a single template directory.
```php
$environment = TemplateLoader::templatePath('templates/', __DIR__);
```

Add Paths: Dynamically add template directories.
```php
$environment->addPath('new_templates/');
```

Create Directories: Automatically create missing directories by passing true as the third variable in your environment class.

```php
$environment = FromArray::templatePath(['templates/'], __DIR__, true);
```


### Caching
Templates are cached with a default TTL of 3600 seconds. Override when rendering:
```php
$view->render('index', 7200); // Cache for 2 hours
```
The Cacheing system depends on symfony Cache

### Clear cache:
```php
$view->clearCache('index'); // Clear specific template
$view->clearCache(); // Clear all templates
```
### Customization

Template Paths: Configure via FromArray or TemplateLoader.
Layout/Component Directories: Set custom names in View constructor.
Cache Settings: Adjust cacheNamespace and cacheTtl.

Error Handling
Wrap rendering in try-catch blocks:
```php
try {
    $output = $view->render('index');
    echo $output;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

Exceptions are thrown for:

Missing template/layout/component files
Invalid paths
Rendering errors
Empty output

### Contributing
```
Submit pull requests or open issues on the repository.
```

### License
```
MIT License
```
Generated on May 17, 2025
