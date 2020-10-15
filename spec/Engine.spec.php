<?php

namespace Wherd\Signal\Spec;

use \Wherd\Signal\Engine;

describe('Compile text', function() {
    beforeEach(fn() => $this->instance = new Engine(__DIR__));

    it('should compile a template without directives and variables', function() {
        expect($this->instance->compileString('Good Morning World!', __FILE__))->toBe('Good Morning World!');
    });

    it('should compile a template that ends with @', function() {
        expect($this->instance->compileString('Good Morning World@'))->toBe('Good Morning World@');
    });

    it('should compile a template with comments', function() {
        expect($this->instance->compileString('Good Morning World!@{- this is a comment -}'))->toBe('Good Morning World!');
        expect($this->instance->compileString('@{- this is a comment -}Good Morning World!'))->toBe('Good Morning World!');
        expect($this->instance->compileString('Good Morning@{- this is a comment -} World!'))->toBe('Good Morning World!');
    });
    
    it('should compile a template with variables', function() {
        expect($this->instance->compileString('Good Morning @{ $name }!'))->toBe('Good Morning <?php echo htmlentities($name) ?>!');
        expect($this->instance->compileString('Good Morning @{! $name }!'))->toBe('Good Morning <?php echo $name ?>!');
    });
    
    it('should compile a template with an email on it', function() {
        expect($this->instance->compileString('Send me an email to <a href="mailto:john@doe.com">john@doe.com</a>'))->toBe('Send me an email to <a href="mailto:john@doe.com">john@doe.com</a>');
    });
    
    it('should compile a template without directives', function() {
        expect($this->instance->compileString('Send me an email to <a href="mailto:john@doe.com">john@doe.com</a> {testing}'))->toBe('Send me an email to <a href="mailto:john@doe.com">john@doe.com</a> {testing}');
    });
    
    it('should compile a templates with custom directives', function() {
        $this->instance->directive('test', fn ($expression) => "<?php echo $expression ?>");
        expect($this->instance->compileString('var testing = @test{$test}'))->toBe('var testing = <?php echo $test ?>');
    });

    it('should compile a ternary expression', function() {
        expect($this->instance->compileString('var testing = @{$is_enabled ? "enabled" : "disabeld"}'))->toBe('var testing = <?php echo htmlentities($is_enabled ? "enabled" : "disabeld") ?>');
    });
});

describe('Compile conditional directives', function() {
    beforeEach(fn() => $this->instance = new Engine(__DIR__));

    it('should compile if directives', function() {
        expect($this->instance->compileString("@if{true, 'ok'}"))->toBe("<?php if (true) : echo htmlentities( 'ok'); endif ?>");
        expect($this->instance->compileString("@if{true, 'ok', 'not ok'}"))->toBe("<?php if (true) : echo htmlentities( 'ok'); else : echo htmlentities( 'not ok'); endif ?>");
        expect($this->instance->compileString("@if{true} ok @end"))->toBe("<?php if (true) : ?> ok <?php endif ?>");
    });

    it('should compile if-elseif-else directives', function() {
        expect($this->instance->compileString("@if{true} if @elseif{true} elseif @else else @end"))->toBe("<?php if (true) : ?> if <?php elseif (true) : ?> elseif <?php else : ?> else <?php endif ?>");
    });
});

describe('Compile loop directives', function() {
    beforeEach(fn() => $this->instance = new Engine(__DIR__));

    it('should compile foreach directives', function() {
        expect($this->instance->compileString('@for{$products as $product}@{$product->name}@end'))->toBe('<?php foreach ($products as $product) : ?><?php echo htmlentities($product->name) ?><?php endforeach ?>');
    });

    it('should compile for directives', function() {
        expect($this->instance->compileString('@for{$i = 0; $i < $count; ++$i}@{$i}@end'))->toBe('<?php for ($i = 0; $i < $count; ++$i) : ?><?php echo htmlentities($i) ?><?php endfor ?>');
    });

    it('should compile while directives', function() {
        expect($this->instance->compileString('@for{$count < 10}@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
    });

    it('should compile break directives', function() {
        expect($this->instance->compileString('@for{$count < 10}@break{$count == 5}@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php if ($count == 5) : break; endif ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
        expect($this->instance->compileString('@for{$count < 10}@if{$count == 5}@break@end@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php if ($count == 5) : ?><?php break ?><?php endif ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
    });

    it('should compile continue directives', function() {
        expect($this->instance->compileString('@for{$count < 10}@continue{$count == 5}@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php if ($count == 5) : continue; endif ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
        expect($this->instance->compileString('@for{$count < 10}@if{$count == 5}@continue@end@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php if ($count == 5) : ?><?php continue ?><?php endif ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
    });

    describe('Compile other directives', function() {
        beforeEach(fn() => $this->instance = new Engine(__DIR__));
    
        it('should compile json directive', function() {
            expect($this->instance->compileString('var products = "@json{$products}";'))->toBe('var products = "<?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT, 512) ?>";');
        });
    
        it('should compile php directives', function() {
            expect($this->instance->compileString('@php $test = 1 + 1; @end'))->toBe('<?php $test = 1 + 1; ?>');
        });
    });

    describe('Compile stack directives', function() {
        beforeEach(fn() => $this->instance = new Engine(__DIR__));
    
        it('should compile push directive', function() {
            expect($this->instance->compileString("@push{'scripts', '<script src=\"main.js\"></script>'}"))->toBe("<?php \$__context->stackPush('scripts', '<script src=\"main.js\"></script>') ?>");
            expect($this->instance->compileString("@push{'scripts'}<script src=\"main.js\"></script>@end"))->toBe("<?php \$__context->stackPush('scripts') ?><script src=\"main.js\"></script><?php \$__context->endStackPush() ?>");
        });

        it('should compile prepend directive', function() {
            expect($this->instance->compileString("@prepend{'scripts', '<script src=\"main.js\"></script>'}"))->toBe("<?php \$__context->stackPrepend('scripts', '<script src=\"main.js\"></script>') ?>");
            expect($this->instance->compileString("@prepend{'scripts'}<script src=\"main.js\"></script>@end"))->toBe("<?php \$__context->stackPrepend('scripts') ?><script src=\"main.js\"></script><?php \$__context->endStackPrepend() ?>");
        });
    });

    describe('Compile heap directives', function() {
        beforeEach(fn() => $this->instance = new Engine(__DIR__));
    
        it('should compile once directive', function() {
            expect($this->instance->compileString("@once{'Just this once'}"))->toMatch("/\<\?php if \(!isset\(\\\$__context->heap\['once_[a-z0-9]{13}'\]\) : \\\$__context->heap\['once_[a-z0-9]{13}'\] = true; echo htmlentities\('Just this once'\); endif \?>/");
            expect($this->instance->compileString("@once Just this once @end"))->toMatch("/\<\?php if \(!isset\(\\\$__context->heap\['once_[a-z0-9]{13}'\]\) : \\\$__context->heap\['once_[a-z0-9]{13}'\] = true; \?> Just this once <\?php endif \?>/");
        });

        it('should compile block directive', function() {
            expect($this->instance->compileString("@block{'title', 'Welcome'}"))->toBe("<?php \$__context->blockPush('title', 'Welcome') ?>");
            expect($this->instance->compileString("@block{'title'}Welcome@end"))->toBe("<?php \$__context->blockPush('title') ?>Welcome<?php \$__context->endBlock() ?>");
        });
    });

    describe('Compile template files', function() {
        beforeEach(fn() => $this->instance = new Engine(__DIR__ . '/stubs'));

        it('should compile single files in debug mode', function() {
            $this->instance->setDebug(true);
            expect($this->instance->compile('included'))->toBe(file_get_contents(__DIR__ . '/stubs/compiled/included.debug.php'));
            expect($this->instance->compile('home'))->toBe(file_get_contents(__DIR__ . '/stubs/compiled/home.debug.php'));
            expect($this->instance->compile('base'))->toBe(file_get_contents(__DIR__ . '/stubs/compiled/base.debug.php'));
        });

        it('should compile single files in production mode', function() {
            expect($this->instance->compile('home'))->toBe(file_get_contents(__DIR__ . '/stubs/compiled/home.prod.php'));
        });
    });

    describe('Render template files', function() {
        beforeEach(fn() => $this->instance = new Engine(__DIR__ . '/stubs'));

        it('should render template files', function() {
            expect($this->instance->render('home', ['content' => '<p>content</p>']))->toBe(file_get_contents(__DIR__ . '/stubs/rendered/home.html'));
        });
    });

    describe('Render cached files', function() {
        beforeEach(fn() => $this->instance = (new Engine(__DIR__ . '/stubs'))->setCacheDirectory(__DIR__ . '/tmp'));

        it('should render template files', function() {
            expect($this->instance->render('home', ['content' => '<p>content</p>']))->toBe(file_get_contents(__DIR__ . '/stubs/rendered/home.html'));
            expect($this->instance->render('home', ['content' => '<p>content</p>']))->toBe(file_get_contents(__DIR__ . '/stubs/rendered/home.html'));
        });
    });

});
