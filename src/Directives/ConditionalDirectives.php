<?php

namespace Wherd\Signal\Directives;

trait ConditionalDirectives
{
    /**
     * Compile if directive.
     * @param string $expression
     * @return string
     */
    protected function compileIf($expression)
    {
        $commaPos = strpos($expression, ',');

        if (false === $commaPos) {
            $this->directivesStack[] = 'if';
            return "<?php if ($expression) : ?>";
        }

        $testExpression = substr($expression, 0, $commaPos);

        $expression = substr($expression, $commaPos + 1);
        $commaPos = strpos($expression, ',', $commaPos + 1);

        if (false === $commaPos) {
            return "<?php if ($testExpression) : echo htmlentities($expression); endif ?>";
        }

        $trueExpression = substr($expression, 0, $commaPos);
        $falseExpression = substr($expression, $commaPos + 1);

        return "<?php if ($testExpression) : echo htmlentities($trueExpression); else : echo htmlentities($falseExpression); endif ?>";
    }

    /**
     * Compile else-if expression.
     * @param string $expression
     * @return string
     */
    public function compileElseif($expression)
    {
        return "<?php elseif ($expression) : ?>";
    }

    /**
     * Compile else expression.
     * @return string
     */
    public function compileElse()
    {
        return "<?php else : ?>";
    }

    /**
     * Compile endif expression.
     * @return string
     */
    public function compileEndif()
    {
        return "<?php endif ?>";
    }

    /**
     * Compile unless directive.
     * @param string $expression
     * @return string
     */
    protected function compileUnless($expression)
    {
        return $this->compileIf("!($expression)");
    }

    /**
     * Compile isset expression.
     * @param string $expression
     * @return string
     */
    public function compileIsset($expression)
    {
        return $this->compileIf("isset($expression)");
    }

    /**
     * Compile empty expression.
     * @param string $expression
     * @return string
     */
    public function compileEmpty($expression)
    {
        return $this->compileIf("empty($expression)");
    }
}
