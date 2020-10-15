<?php

namespace Wherd\Signal\Directives;

trait StackDirectives
{
    /**
     * Compile stack push.
     * @param string $expression
     * @return string
     */
    protected function compilePush($expression)
    {
        if (false === strpos($expression, ',')) {
            $this->directivesStack[] = 'push';
        }
        
        return "<?php \$__context->stackPush($expression) ?>";
    }

    /**
     * Compile end stack push.
     * @return string
     */
    protected function compileEndpush()
    {
        return "<?php \$__context->endStackPush() ?>";
    }

    /**
     * Compile stack prepend.
     * @param string $expression
     * @return string
     */
    protected function compilePrepend($expression)
    {
        if (false === strpos($expression, ',')) {
            $this->directivesStack[] = 'prepend';
        }
        
        return "<?php \$__context->stackPrepend($expression) ?>";
    }

    /**
     * Compile end stack prepend.
     * @return string
     */
    protected function compileEndprepend()
    {
        return "<?php \$__context->endStackPrepend() ?>";
    }

    /**
     * Yield stack directive.
     * @param string $expression
     * @return string
     */
    protected function compileStack($expression)
    {
        return "<?php \$__context->getStack($expression) ?>";
    }
}
