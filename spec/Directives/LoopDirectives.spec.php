<?php

namespace Wherd\Spec\Signal\Directives;

use Wherd\Signal\Compiler;

describe('Compile loop directives', function () {
    beforeEach(fn () => $this->instance = new Compiler(__DIR__));

    it('should compile foreach directives', function () {
        expect($this->instance->compileString('@for{$products as $product}@{$product->name}@end'))->toBe('<?php foreach ($products as $product) : ?><?php echo htmlentities($product->name) ?><?php endforeach ?>');
    });

    it('should compile for directives', function () {
        expect($this->instance->compileString('@for{$i = 0; $i < $count; ++$i}@{$i}@end'))->toBe('<?php for ($i = 0; $i < $count; ++$i) : ?><?php echo htmlentities($i) ?><?php endfor ?>');
    });

    it('should compile while directives', function () {
        expect($this->instance->compileString('@for{$count < 10}@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
    });

    it('should compile break directives', function () {
        expect($this->instance->compileString('@for{$count < 10}@break{$count == 5}@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php if ($count == 5) break ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
        expect($this->instance->compileString('@for{$count < 10}@if{$count == 5}@break@end@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php if ($count == 5) : ?><?php break ?><?php endif ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
    });

    it('should compile continue directives', function () {
        expect($this->instance->compileString('@for{$count < 10}@continue{$count == 5}@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php if ($count == 5) continue ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
        expect($this->instance->compileString('@for{$count < 10}@if{$count == 5}@continue@end@{$count++}@end'))->toBe('<?php while ($count < 10) : ?><?php if ($count == 5) : ?><?php continue ?><?php endif ?><?php echo htmlentities($count++) ?><?php endwhile ?>');
    });
});
