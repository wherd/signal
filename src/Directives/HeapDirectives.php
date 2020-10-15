<?php

namespace Wherd\Signal\Directives;

trait HeapDirectives
{
    /**
     * Once directive.
     * @param string|null $expression
     * @return string
     */
    protected function compileOnce($expression)
    {
        $id = uniqid('once_');
        
        if (null !== $expression) {
            return "<?php if (!isset(\$__context->heap['$id']) : \$__context->heap['$id'] = true; echo htmlentities($expression); endif ?>";
        }

        $this->directivesStack[] = 'if';
        return "<?php if (!isset(\$__context->heap['$id']) : \$__context->heap['$id'] = true; ?>";
    }

    /**
     * Compile block definition.
     * @param string $expression
     * @return string
     */
    protected function compileBlock($expression)
    {
        if (false === strpos($expression, ',')) {
            $this->directivesStack[] = 'block';
        }
        
        return "<?php \$__context->blockPush($expression) ?>";
    }

    /**
     * Compile block is defined.
     * @param string $expression
     * @return string
     */
    protected function compileBlockExists($expression)
    {
        return "<?php \$__context->blockExists($expression) ?>";
    }

    /**
     * Compile end block definition.
     * @return string
     */
    protected function compileEndblock()
    {
        return "<?php \$__context->endBlock() ?>";
    }

    /**
     * Compile end block with show directive definition.
     * @return string
     */
    protected function compileShow()
    {
        return "<?php \$__context->endBlock(true) ?>";
    }

    /**
     * Compile parent block.
     * @return string
     */
    protected function compileParent()
    {
        return "<?php echo \$__context->getBlockContent(\$__context->getCurrentBlock()) ?>";
    }

    /**
     * Compile parent block.
     * @param string $expression
     * @return string
     */
    protected function compileYield($expression)
    {
        $commaPos = strpos($expression, ',');

        if (false !== $commaPos) {
            $name = '@heap_' . md5(trim(substr($expression, 0, $commaPos), " \"'\t\n\r\0\x0B"));
            return "<?php \$__context->blockPush($expression); ?>$name";
        }

        return '@heap_' . md5(trim($expression, " \"'\t\n\r\0\x0B"));
    }
}
