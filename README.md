# Signal

Yet another template engine.

## Installation

Install using composer:

```bash
composer require wherd/signal
```

## Introduction

Signal is yet another simple templating engine. All Signal templates are compiled into plain PHP code and cached, meaning Signal adds essentially zero overhead to your application. Signal template files use the .signal.php file extension.

Create a signal instance by passing it the folder(s) where your view files are located. Render a template by calling the `render` method.

```php
use Signal\Compiler;
use Signal\View;

$compiler = new Compiler(__DIR__ . '/views');
$compiler->setCacheDirectory(__DIR__ . '/tmp');

$signal = new View($compiler);

echo $signal->render('homepage', ['name' => 'John Doe']);
```

## Displaying Data
You may display data that is passed to your Signal views by wrapping the variable. For example, given the following:

```php
use Signal\Compiler;
use Signal\View;

$compiler = new Compiler(__DIR__ . '/views');
$compiler->setCacheDirectory(__DIR__ . '/tmp');

$signal = new View($compiler);

echo $signal->render('homepage', ['name' => 'John Doe']);
```

You may display the contents of the name variable like so:

`Hello, @{ $name }.`

You are not limited to displaying the contents of the variables passed to the view. You may also echo the results of any PHP function. In fact, you can put any PHP code you wish inside of a Signal statement:

`The current UNIX timestamp is @{ time() }}.`

## Displaying Unescaped Data

By default, Signal @{ } statements are automatically sent through PHP's htmlspecialchars function to prevent XSS attacks. If you do not want your data to be escaped, you may use the following syntax:

`Hello, @{! $name }.`

// TODO