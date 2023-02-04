<?php

namespace Wherd\Spec\Signal;

use Wherd\Signal\Compiler;
use Wherd\Signal\View;

describe('Compile text', function () {
    beforeEach(fn () => $this->instance = new Compiler(__DIR__));

    it('should compile a template without directives and variables', function () {
        expect($this->instance->compileString('Good Morning World!', __FILE__))->toBe('Good Morning World!');
    });

    it('should compile a template that ends with @', function () {
        expect($this->instance->compileString('Good Morning World@'))->toBe('Good Morning World@');
    });

    it('should compile a template with comments', function () {
        expect($this->instance->compileString('Good Morning World!@{- this is a comment -}'))->toBe('Good Morning World!');
        expect($this->instance->compileString('@{- this is a comment -}Good Morning World!'))->toBe('Good Morning World!');
        expect($this->instance->compileString('Good Morning@{- this is a comment -} World!'))->toBe('Good Morning World!');
    });

    it('should compile a template with variables', function () {
        expect($this->instance->compileString('Good Morning @{ $name }!'))->toBe('Good Morning <?php echo htmlentities($name) ?>!');
        expect($this->instance->compileString('Good Morning @{! $name }!'))->toBe('Good Morning <?php echo $name ?>!');
    });

    it('should compile a template with an email on it', function () {
        expect($this->instance->compileString('Send me an email to <a href="mailto:john@doe.com">john@doe.com</a>'))->toBe('Send me an email to <a href="mailto:john@doe.com">john@doe.com</a>');
    });

    it('should compile a template without directives', function () {
        expect($this->instance->compileString('Send me an email to <a href="mailto:john@doe.com">john@doe.com</a> {testing}'))->toBe('Send me an email to <a href="mailto:john@doe.com">john@doe.com</a> {testing}');
    });

    it('should compile a templates with custom directives', function () {
        $this->instance->registerDirective('test', fn ($expression) => "<?php echo $expression ?>");
        expect($this->instance->compileString('var testing = @test{$test}'))->toBe('var testing = <?php echo $test ?>');
    });

    it('should compile a ternary expression', function () {
        expect($this->instance->compileString('var testing = @{$is_enabled ? "enabled" : "disabeld"}'))->toBe('var testing = <?php echo htmlentities($is_enabled ? "enabled" : "disabeld") ?>');
    });
});

describe('Compile template files', function () {
    beforeEach(fn () => $this->instance = new Compiler(__DIR__ . '/stubs'));

    it('should compile single files in debug mode', function () {
        $this->instance->debugMode = true;
        expect($this->instance->compile('included'))->toBe(file_get_contents(__DIR__ . '/stubs/compiled/included.debug.php'));
        expect($this->instance->compile('base'))->toBe(file_get_contents(__DIR__ . '/stubs/compiled/base.debug.php'));
        expect($this->instance->compile('home'))->toBe(file_get_contents(__DIR__ . '/stubs/compiled/home.debug.php'));
    });

    it('should compile single files in production mode', function () {
        expect($this->instance->compile('home'))->toBe(file_get_contents(__DIR__ . '/stubs/compiled/home.prod.php'));
    });
});

describe('Render template files', function () {
    beforeEach(fn () => $this->instance = new View(new Compiler(__DIR__ . '/stubs')));

    it('should render template files', function () {
        expect($this->instance->render('home', ['content' => '<p>content</p>']))->toBe(file_get_contents(__DIR__ . '/stubs/rendered/home.html'));
    });
});
