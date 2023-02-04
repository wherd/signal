<?php

namespace Wherd\Spec\Signal\Directives;

use Wherd\Signal\Compiler;

describe('Compile section directives', function () {
    beforeEach(fn () => $this->instance = new Compiler(__DIR__));

    it('should compile once directive', function () {
        expect($this->instance->compileString("@once{'Just this once'}"))->toMatch("/<\?php if \(!isset\(\\\$this->section\['once_[a-z0-9]{13}']\) : \\\$this->section\['once_[a-z0-9]{13}'] = true; echo htmlentities\('Just this once'\); endif \?>/");
        expect($this->instance->compileString("@once Just this once @end"))->toMatch("/\<\?php if \(!isset\(\\\$this->section\['once_[a-z0-9]{13}'\]\) : \\\$this->section\['once_[a-z0-9]{13}'\] = true \?> Just this once <\?php endif \?>/");
    });

    it('should compile section directive', function () {
        expect($this->instance->compileString("@section{'title', 'Welcome'}"))->toBe("<?php \$this->section('title', 'Welcome') ?>");
        expect($this->instance->compileString("@section{'title'}Welcome@end"))->toBe("<?php \$this->section('title') ?>Welcome<?php \$this->endSection() ?>");
    });
});
