<?php

namespace Wherd\Spec\Signal\Directives;

use Wherd\Signal\Compiler;

describe('Compile conditional directives', function () {
    beforeEach(fn () => $this->instance = new Compiler(__DIR__));

    it('should compile if directives', function () {
        expect($this->instance->compileString("@if{true, 'ok'}"))->toBe("<?php if (true) echo htmlentities( 'ok') ?>");
        expect($this->instance->compileString("@if{true, 'ok', 'not ok'}"))->toBe("<?= htmlentities(true ?  'ok' :  'not ok') ?>");
        expect($this->instance->compileString("@if{true} ok @end"))->toBe("<?php if (true) : ?> ok <?php endif ?>");
    });

    it('should compile if-elseif-else directives', function () {
        expect($this->instance->compileString("@if{true} if @elseif{true} elseif @else else @end"))->toBe("<?php if (true) : ?> if <?php elseif (true) : ?> elseif <?php else : ?> else <?php endif ?>");
    });
});
