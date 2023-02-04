<?php

namespace Wherd\Spec\Signal\Directives;

use Wherd\Signal\Compiler;

describe('Compile other directives', function () {
    beforeEach(fn () => $this->instance = new Compiler(__DIR__));

    it('should compile json directive', function () {
        expect($this->instance->compileString('var products = "@json{$products}";'))->toBe('var products = "<?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT, 512) ?>";');
    });

    it('should compile php directives', function () {
        expect($this->instance->compileString('@php $test = 1 + 1; @end'))->toBe('<?php $test = 1 + 1; ?>');
    });
});
