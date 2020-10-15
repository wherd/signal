<?php

/*
 * Signal - Yet another template engine.
 * Copyright (c) 2020 Wherd (https://www.wherd.dev).
 */

namespace Wherd\Signal;

use RuntimeException;

class Context
{
    /**
     * Allow directives to save named content for later use blocks of content.
     * @var array<string,string>
     */
    protected $heap = [];

    /**
     * Allow directives to register stack of content.
     * @var array<string,array<string>>
     */
    protected $stack = [];

    /**
     * Holds a list of blocks and stacks being generated.
     * @var array<string>
     */
    protected $operationStack = [];

    /**
     * Terminator callbacks to run before rendering.
     * @var array<callable>
     */
    protected $terminator = [];

    /**
     * Register terminator.
     * @param callable $callback
     * @return self
     */
    public function setTerminator($callback)
    {
        $this->terminator[] = $callback;
        return $this;
    }

    /**
     * Terminate template rendering.
     * @param string $output
     * @return string
     */
    public function terminate($output)
    {
        foreach ($this->terminator as $callback) {
            $output = $callback($output);
        }

        $output = $this->renderStack($output);
        $output = $this->renderHeap($output);

        return $output;
    }

    /**
     * Push to stack directive.
     * @param string $name
     * @param string $default
     * @param string $op
     * @return void
     */
    public function startStackPush($name, $default='', $op='push')
    {
        if ('' !== $default) {
            if (!isset($this->stack[$name])) {
                $this->stack[$name] = [];
            }

            if ('push' === $op) {
                $this->stack[$name][] = $default;
            } else {
                array_unshift($this->stack[$name], $default);
            }
            return;
        }

        $this->operationStack[] = $name;
        ob_start();
    }

    /**
     * End push directive
     * @param string $op
     * @return void
     */
    public function endStackPush($op='push')
    {
        if (empty($this->operationStack)) {
            throw new RuntimeException('Cannot end a push stack without first starting one.');
        }

        $name = array_pop($this->operationStack);
        $content = ob_get_clean() ?: '';

        if (!isset($this->stack[$name])) {
            $this->stack[$name] = [];
        }

        if ('push' === $op) {
            $this->stack[$name][] = $content;
        } else {
            array_unshift($this->stack[$name], $content);
        }
    }

    /**
     * Prepend to stack directive.
     * @param string $name
     * @param string $default
     * @return void
     */
    public function startStackPrepend($name, $default='')
    {
        $this->startStackPush($name, $default, 'prepend');
    }

    /**
     * End prepend to stack
     * @return void
     */
    public function endStackPrepend()
    {
        $this->endStackPush('prepend');
    }

    /**
     * Ensure stack exists.
     * @param string $name
     * @return string
     */
    public function getStack($name)
    {
        if (!isset($this->stack[$name])) {
            $this->stack[$name] = [];
        }

        return '@stack_' . md5($name);
    }

    /**
     * Render stacks.
     * @param string $output
     * @return string
     */
    protected function renderStack($output)
    {
        foreach ($this->stack as $name => $stack) {
            $directive = '@stack_' . md5($name);
            $output = str_replace($directive, empty($stack) ? '' : implode("\n", $stack), $output);
        }

        return $output;
    }

    /**
     * Do block push directive.
     * @param string $name
     * @param string $default
     * @return void
     */
    public function blockPush($name, $default='')
    {
        if ('' !== $default) {
            $this->heap[$name] = $default;
            return;
        }

        $this->operationStack[] = trim($name);
        ob_start();
    }

    /**
     * End block directive
     * @param bool $yield
     * @return void
     */
    public function endBlock($yield=false)
    {
        if (empty($this->operationStack)) {
            throw new RuntimeException('Cannot end a block without first starting one.');
        }

        $name = array_pop($this->operationStack) ?? '';
        $content = ob_get_clean() ?: '';

        $this->heap[$name] = $content;

        if ($yield) {
            echo '@heap_' . md5($name);
        }
    }

    /**
     * Render heaps.
     * @param string $output
     * @return string
     */
    protected function renderHeap($output)
    {
        foreach ($this->heap as $name => $content) {
            $directive = '@heap_' . md5($name);
            $output = str_replace($directive, $content, $output);
        }

        return $output;
    }

    /**
     * Get current section.
     * @return string
     */
    public function getCurrentBlock()
    {
        return $this->operationStack[count($this->operationStack) - 1] ?: '';
    }

    /**
     * Get current section.
     * @param string $name
     * @return bool
     */
    public function blockExists($name)
    {
        return isset($this->heap[trim($name)]);
    }

    /**
     * Get current section.
     * @param string $name
     * @return string
     */
    public function getBlockContent($name)
    {
        return $this->heap[$name] ?? '';
    }
}
