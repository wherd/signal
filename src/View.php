<?php

declare(strict_types=1);

namespace Wherd\Signal;

use RuntimeException;

class View
{
    protected string $layout;

    /** @var array<string,mixed> */
    protected array $globals = [];

    /** @var array<string,string> */
    protected array $section = [];

    /** @var array<string> */
    protected array $operationStack = [];

    /** @var array<callable> */
    protected array $terminator = [];

    public function __construct(
        protected Compiler $compiler,
    ) {
    }

    public function setGlobal(string $name, mixed $value): void
    {
        $this->globals[$name] = $value;
    }

    /** @param array<string,mixed> $variables */
    public function addGlobals(array $variables): void
    {
        $this->globals += $variables;
    }

    /** @param array<string,mixed> $variables */
    public function render(string $name, array $variables=[]): string
    {
        ob_start();

        extract($variables, EXTR_SKIP);
        extract($this->globals, EXTR_SKIP);

        $needsRebuild = $this->compiler->needsRebuild($name);
        $cacheFile = $this->compiler->getCacheFile($name) ?? '';

        if (!$needsRebuild && $cacheFile && file_exists($cacheFile)) {
            include $cacheFile;
        } else {
            $code = $this->compiler->compile($name);
            eval('?>' . $code . '<?php ');
        }

        $output = ob_get_clean() ?: '';

        if (!empty($this->layout)) {
            $layout = $this->layout;
            $this->layout = '';
            $output = $this->render($layout, $variables);
        }

        return $output;
    }

    public function extends(string $template): void
    {
        $this->layout = $template;
    }

    /** @param array<string,mixed> $variables */
    public function include(string $name, array $variables = []): void
    {
        ob_start();

        extract($variables, EXTR_SKIP);
        extract($this->globals, EXTR_SKIP);

        $needsRebuild = $this->compiler->needsRebuild($name);
        $cacheFile = $this->compiler->getCacheFile($name) ?? '';

        if (!$needsRebuild && $cacheFile && file_exists($cacheFile)) {
            include $cacheFile;
        } else {
            $code = $this->compiler->compile($name);
            eval('?>' . $code . '<?php ');
        }

        echo ob_get_clean() ?: '';
    }

    public function setTerminator(callable $callback): void
    {
        $this->terminator[] = $callback;
    }

    public function terminate(string $output): string
    {
        foreach ($this->terminator as $callback) {
            $output = $callback($output);
        }

        return $output;
    }

    public function section(string $name, ?string $default=null): void
    {
        if (null !== $default) {
            $this->section[$name] = $default;
            return;
        }

        $this->operationStack[] = $name;
        ob_start();
    }

    public function endSection(bool $yield=false): void
    {
        if (empty($this->operationStack)) {
            throw new RuntimeException('Cannot end a block without first starting one.');
        }

        $name = array_pop($this->operationStack) ?: '';
        $content = ob_get_clean() ?: '';

        if (!empty($this->section[$name])) {
            $content = str_replace('<!-- PARENT(' . $name . ') -->', $content, $this->section[$name]);
        }

        $this->section[$name] = $content;

        if ($yield) {
            echo $this->section[$name];
        }
    }

    protected function displaySection(string $name, string $default = ''): string
    {
        return $this->section[$name] ?? $default;
    }

    public function getCurrentSection(): string
    {
        return $this->operationStack[count($this->operationStack) - 1] ?? '';
    }

    public function sectionExists(string $name): bool
    {
        return isset($this->section[$name]);
    }

    public function sectionMissing(string $name): bool
    {
        return !isset($this->section[$name]);
    }
}
