# Signal

Yet another template engine. Inspired in Laravel's Blade template engine.

## Installation

Install using composer:

```bash
composer require wherd/signal
```

## Usage

Create a signal instance by passing it the folder(s) where your view files are located. Render a template by calling the `render` method.

```php
use Wherd\Signal\Engine;

$signal = new Engine(__DIR__ . '/views');
$signal->setCacheDirectory(__DIR__ . '/tmp');

echo $signal->render('homepage', ['name' => 'John Doe']);
```

You can also extend Singal using the `directive()` function:

```php
$signal->directive(
  'datetime',
  fn ($expression) => "<?php echo (new DateTime($expression))->format('F d, Y g:i a'); ?>"
);
```

Which allows you to use the following in your signal template:

```
Current date: @datetime($date)
```
