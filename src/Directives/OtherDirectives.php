<?php

namespace Wherd\Signal\Directives;

trait OtherDirectives
{
    /**
     * Compile PHP directive.
     * @param string|null $expression
     * @return string
     */
    protected function compilePhp($expression)
    {
        if (null !== $expression) {
            return "<?php $expression ?>";
        }

        $this->directivesStack[] = 'php';
        return '<?php';
    }

    /**
     * Compile end PHP directive.
     * @return string
     */
    protected function compileEndphp()
    {
        return '?>';
    }

    /**
     * Compile json directive.
     * @param string $expression
     * @return string
     */
    protected function compileJson($expression)
    {
        $parts = explode(',', $expression);
        $options = $parts[1] ?? 'JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT';
        $depth = $parts[2] ?? 512;

        return "<?php echo json_encode($parts[0], $options, $depth) ?>";
    }

    /**
     * Compile trim directive.
     * @return string
     */
    protected function compileTrim()
    {
        return "<?php ob_start() ?>";
    }

    /**
     * Compile end trim directive.
     * @return string
     */
    protected function compileEndtrim()
    {
        return "<?php echo trim(ob_get_clean()) ?>";
    }

    /**
     * Include external template file.
     * @param string $expression
     * @return string
     */
    protected function compileExtends($expression)
    {
        return $this->compileInclude($expression);
    }

    /**
     * Include external template file.
     * @param string $expression
     * @return string
     */
    protected function compileInclude($expression)
    {
        if ($this->debug) {
            return "<?php \$__context->include($expression) ?>";
        }
        
        $name = trim($expression, '\'"');
        echo $this->compile($name);

        return '';
    }
}
