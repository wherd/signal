<?php

/*
 * Signal - Yet another template engine.
 * Copyright (c) 2020 Wherd (https://www.wherd.dev).
 */

namespace Wherd\Signal;

use ErrorException;
use InvalidArgumentException;
use RuntimeException;

class Engine
{
    use \Wherd\Signal\Directives\ConditionalDirectives,
        \Wherd\Signal\Directives\HeapDirectives,
        \Wherd\Signal\Directives\LoopDirectives,
        \Wherd\Signal\Directives\OtherDirectives,
        \Wherd\Signal\Directives\StackDirectives;

    /**
     * Enable or disable debug mode.
     * @var bool
     */
    protected $debug = false;

    /**
     * Working directory.
     * @var string
     */
    protected $workingDirectory;

    /**
     * Where to put generated template files.
     * @var string
     */
    protected $cacheDirectory;

    /**
     * Registered directory alias.
     * @var array<string,string>
     */
    protected $alias = [];

    /**
     * Template global variables.
     * @var array<string,mixed>
     */
    protected $globals = [];

    /**
     * Registered directives.
     * @var array<string,callable>
     */
    protected $directives = [];

    /**
     * List of directives being analysed.
     * @var array<string>
     */
    protected $directivesStack = [];

    /**
     * Create a new template compiler with the given working directory.
     * @param string $workingDirectory
     */
    public function __construct($workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * Register new variable.
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setGlobal($name, $value)
    {
        $this->globals[$name] = $value;
        return $this;
    }

    /**
     * Add multiple variables.
     * @param array<string,mixed> $variables
     * @return self
     */
    public function setGlobals($variables)
    {
        $this->globals += $variables;
        return $this;
    }

    /**
     * Set debug mode.
     * @param bool $on
     * @return self
     */
    public function setDebug($on=false)
    {
        $this->debug = $on;
        return $this;
    }

    // Directives -------------------------------------------------------------
   
    /**
     * Register new directive function.
     * @param string $name
     * @param callable $callback
     * @return self
     */
    public function directive($name, $callback)
    {
        if ($this->isDirective($name)) {
            throw new InvalidArgumentException("Directive '$name' is already registered.");
        }

        $this->directives[$name] = $callback;
        return $this;
    }

    /**
     * Name is a registered directive.
     * @param string $name
     * @return bool
     */
    protected function isDirective($name)
    {
        return (isset($this->directives[$name]) || is_callable([$this, 'compile' . ucfirst($name)]));
    }

    /**
     * Execute registered directive.
     * @param string $directive
     * @param string|null $expression
     * @return string
     */
    protected function callDirective($directive, $expression=null)
    {
        if (isset($this->directives[$directive])) {
            $fn = $this->directives[$directive];
            return $fn($expression);
        }
        
        return $this->{'compile' . ucfirst($directive)}($expression);
    }

    // Renderer -------------------------------------------------------------

    /**
     * Render template.
     * @param string $name
     * @param array<string,mixed> $variables
     * @return string
     */
    public function render($name, $variables=[])
    {
        ob_start();

        extract($variables, EXTR_SKIP);
        extract($this->globals, EXTR_SKIP);

        $__context = new Context;

        $needsRebuild = $this->needsRebuild($name);
        $cacheFile = $this->getCacheFile($name) ?? '';

        if (!$needsRebuild && $cacheFile && file_exists($cacheFile)) {
            include $cacheFile;
        } else {
            $code = $this->compile($name);
            eval('?>' . $code . '<?php ');
        }

        $output = ob_get_clean() ?: '';
        return $__context->terminate($output);
    }

    // Compiler ---------------------------------------------------------------

    /**
     * Compile template.
     * @param string $name
     * @return string
     */
    public function compile($name)
    {
        $filename = $this->getTemplateFile($name);
        $cacheFile = $this->getCacheFile($name);
        
        $lock = null;

        if ($cacheFile) {
            $lock = $this->acquireLock("$cacheFile.lock", LOCK_EX);
        }

        $code = $this->compileString(file_get_contents($filename) ?: '');

        if ($cacheFile && $lock) {
            if (file_put_contents("$cacheFile.tmp", $code) !== strlen($code) || !rename("$cacheFile.tmp", $cacheFile)) {
                unlink("$cacheFile.tmp");
                throw new RuntimeException("Unable to create '$cacheFile'.");
            }

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($cacheFile, true);
            }

            flock($lock, LOCK_UN);
        }

        return $code;
    }

    /**
     * Compile a beard string.
     * @param string $source
     * @param string $filename
     * @return string
     */
    public function compileString($source, $filename = '')
    {
        $previousOffset = 0;
        $length = strlen($source);

        try {
            ob_start();

            while ($previousOffset < $length && false !== ($offset = strpos($source, '@', (int) $previousOffset))) {
                // echo normal text
                if ($previousOffset < $offset) {
                    echo substr($source, (int) $previousOffset, $offset - $previousOffset);
                }

                $previousOffset = $offset;

                // template may end with an @
                if (!isset($source[$offset+1])) {
                    break;
                }

                // @{.*}
                if ('{' === $source[$offset+1]) {
                    $offset += 2; // skip @{

                    if (!isset($source[$offset])) {
                        throw new RuntimeException('Unexpected end of file.');
                    }

                    // @{- a comment -}
                    if ('-' === $source[$offset]) {
                        $offset = strpos($source, '-}', $offset);

                        if (false === $offset) {
                            throw new RuntimeException('Unexpected end of file. Expecting end of comment.');
                        }
                        
                        $previousOffset = $offset + 2; // skip -}
                        continue;
                    }
                    
                    $isUnsafe = false;

                    if ('!' === $source[$offset]) {
                        $isUnsafe = true;
                        $offset += 1; // skip -
                    }

                    $previousOffset = $offset;
                    $offset = strpos($source, '}', $offset);

                    if (false === $offset) {
                        throw new RuntimeException('Unexpected end of file.');
                    }

                    echo '<?php echo',
                        ($isUnsafe ? ' ' : ' htmlentities('),
                        trim(substr($source, $previousOffset, $offset - $previousOffset)),
                        ($isUnsafe ? ' ?>' : ') ?>');

                    $previousOffset = $offset + 1;
                    continue;
                }

                // @directive OR @directive{.*}
                $offset = strpos($source, '{', $previousOffset);
                $directive = trim(substr($source, $previousOffset + 1, $offset - ($previousOffset + 1)));
                $hasExpression = true;

                if (false === $offset || !$this->isDirective($directive)) { // try empty directives @directive
                    $offset = $previousOffset;
                    while (isset($source[++$offset]) && ctype_alnum($source[$offset]));

                    $directive = trim(substr($source, $previousOffset + 1, $offset - ($previousOffset + 1)));
                    $hasExpression = false;
                    $offset -= 1;
                }

                if (!$this->isDirective($directive)) {
                    echo substr($source, $previousOffset, $offset - ($previousOffset));
                    $previousOffset = $offset;
                    continue;
                }

                $expression = null;
                $previousOffset = $offset + 1; // skip @directive{

                if ($hasExpression) {
                    $offset = strpos($source, '}', (int) $previousOffset);
        
                    if (false === $offset) {
                        throw new RuntimeException('Unexpected end of file. Expecting delimiter closer.');
                    }
        
                    $expression = trim(substr($source, $previousOffset, $offset - $previousOffset));
                    $previousOffset = $offset + 1;
                }
                
                echo $this->callDirective($directive, $expression);
            }

            // echo rest of text
            echo substr($source, (int) $previousOffset, $length - $previousOffset);
            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            $lineno = substr_count($source, "\n", 0, $previousOffset);
            throw new ErrorException($e->getMessage(), 0, E_ERROR, $filename, $lineno);
        }
    }

    /**
     * Compile end expression.
     * @return string
     */
    protected function compileEnd()
    {
        while (null !== ($directive = array_pop($this->directivesStack))) {
            $directive = 'end' . $directive;
            if ($this->isDirective($directive)) {
                return $this->callDirective($directive);
            }
        }

        throw new InvalidArgumentException('You should open a directive before closing it.');
    }

    // File Handler -----------------------------------------------------------

    /**
     * Set cache folder.
     * @param string $path
     * @return self
     */
    public function setCacheDirectory($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        touch("$path/.touch");

        $this->cacheDirectory = $path;
        return $this;
    }

    /**
     * Force engine to rebuild all cache.
     * @return self
     */
    public function flushCache()
    {
        if ($this->cacheDirectory) {
            touch("{$this->cacheDirectory}/.touch");
        }
    }

    /**
     * Cached template file needs rebuild?
     * @param string $name
     * @return bool
     */
    protected function needsRebuild($name)
    {
        if (!$this->cacheDirectory) {
            return true;
        }

        $touchTime = (int) filemtime("{$this->cacheDirectory}/.touch");
        $cacheTime = (int) filemtime($this->getCacheFile($name) ?? '');

        if (!$this->debug) {
            return $touchTime > $cacheTime;
        }

        $templateFile = $this->getTemplateFile($name);
        return $touchTime > $cacheTime || filemtime($templateFile) > $cacheTime;
    }

    /**
     * Register a template path alias.
     * @param string $alias
     * @param string $directory
     * @return self
     */
    public function alias($alias, $directory)
    {
        if (isset($this->alias[$alias])) {
            throw new RuntimeException("Alias '$alias' is already registered.");
        }

        $this->alias[$alias] = $directory;
        return $this;
    }

    /**
     * Get cache file path.
     * @param string $name
     * @return string|null
     */
    protected function getCacheFile($name)
    {
        if (isset($this->cacheDirectory)) {
            $filename = md5($name);
            $path = $this->debug ? 'dev' : 'prod';
            return "{$this->cacheDirectory}/{$path}/{$filename[0]}/{$filename}.php";
        }

        return null;
    }

    /**
     * Get path to template file.
     * @param string $name
     * @return string
     */
    protected function getTemplateFile($name)
    {
        $basepath = $this->workingDirectory;

        if (false !== ($offset = strpos($name, ':'))) {
            $alias = substr($name, 0, $offset);

            if (!isset($this->alias[$alias])) {
                throw new RuntimeException("Alias '$alias' is not registered.");
            }

            $basepath = $this->alias[$alias];
            $name = substr($name, $offset + 1);
        }

        return $basepath . '/' . ltrim($name, '/') . '.signal.php';
    }

    /**
     * Aquire file lock.
     * @param string $filename
     * @param int $mode
     * @return resource|false
     */
    protected function acquireLock($filename, $mode)
    {
        $dir = dirname($filename);

        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create directory '$dir'.");
        }

        $handle = fopen($filename, 'w');

        if (!$handle) {
            throw new RuntimeException("Unable to create file '$filename'. ");
        } elseif (!flock($handle, $mode)) {
            throw new RuntimeException('Unable to acquire ' . ($mode & LOCK_EX ? 'exclusive' : 'shared') . " lock on file '$filename'.");
        }

        return $handle;
    }
}
