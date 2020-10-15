<?php

namespace Wherd\Signal\Directives;

trait LoopDirectives
{
    /**
     * Compile for directive.
     * @param string $expression
     * @return string
     */
    protected function compileFor($expression)
    {
        $test = strpos($expression, ' as '); // for ($products as $product)
        
        if (false !== $test) {
            $this->directivesStack[] = 'foreach';
            return "<?php foreach ($expression) : ?>";
        }

        $test = strpos($expression, ';'); // for ($i = 0; $i < count($products); ++$i)

        if (false !== $test) {
            $this->directivesStack[] = 'for';
            return "<?php for ($expression) : ?>";
        }

        $this->directivesStack[] = 'while';
        return "<?php while ($expression) : ?>";
    }

    /**
     * Forelse directive.
     * @param string $expression
     * @return string
     */
    protected function compileForelse($expression)
    {
        $offset = strpos($expression, ' ');
        $test = substr($expression, 0, (int) $offset);

        $this->directivesStack[] = 'forelse';
        return "<?php if (!empty($test)): foreach($expression) : ?>";
    }

    /**
     * Compile endfor expression.
     * @return string
     */
    public function compileEndfor()
    {
        return "<?php endfor ?>";
    }

    /**
     * Compile endeforeach expression.
     * @return string
     */
    public function compileEndforeach()
    {
        return "<?php endforeach ?>";
    }

    /**
     * Compile endwhile expression.
     * @return string
     */
    public function compileEndwhile()
    {
        return "<?php endwhile ?>";
    }

    /**
     * Compile endfor-else expression.
     * @return string
     */
    public function compileEndforelse()
    {
        return "<?php endforeach; endif; ?>";
    }

    /**
     * Continue directive.
     * @param string|null $expression
     * @return string
     */
    protected function compileContinue($expression)
    {
        if (null !== $expression) {
            return "<?php if ($expression) : continue; endif ?>";
        }

        return '<?php continue ?>';
    }

    /**
     * Break directive.
     * @param string|null $expression
     * @return string
     */
    protected function compileBreak($expression)
    {
        if (null !== $expression) {
            return "<?php if ($expression) : break; endif ?>";
        }

        return '<?php break ?>';
    }
}
