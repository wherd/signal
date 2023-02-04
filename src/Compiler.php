<?php

declare(strict_types=1);

namespace Wherd\Signal;

use ErrorException;
use InvalidArgumentException;
use RuntimeException;

class Compiler
{
    protected ?string $cacheDirectory;

    public bool $debugMode = false;

    /** @var array<string,string> */
    protected array $alias = [];

    /** @var array<string,callable> */
    protected array $directives = [];

    /** @var array<string> */
    protected array $directivesStack = [];

    public function __construct(
        protected string $workingDirectory,
    ) {
    }

    public function registerDirective(string $name, callable $callback): void
    {
        if ($this->isDirective($name)) {
            throw new InvalidArgumentException("Directive '$name' is already registered.");
        }

        $this->directives[$name] = $callback;
    }

    protected function isDirective(string $name): bool
    {
        return isset($this->directives[$name]) || method_exists($this, 'compile' . ucfirst($name));
    }

    protected function callDirective(string $directive, ?string $expression = null): string
    {
        if (isset($this->directives[$directive])) {
            $fn = $this->directives[$directive];
            return $fn($expression);
        }

        return $this->{'compile' . ucfirst($directive)}($expression);
    }

    public function compile(string $name): string
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
                throw new RuntimeException("Unable to create '$cacheFile'");
            }

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($cacheFile, true);
            }

            flock($lock, LOCK_UN);
        }

        return $code;
    }

    public function compileString(string $source, string $filename = ''): string
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
                        $offset += 1; // skip !
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

    protected function compileIf(string $expression): string
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
            return "<?php if ($testExpression) echo htmlentities($expression) ?>";
        }

        $trueExpression = substr($expression, 0, $commaPos);
        $falseExpression = substr($expression, $commaPos + 1);

        return "<?= htmlentities($testExpression ? $trueExpression : $falseExpression) ?>";
    }

    protected function compileElseif(string $expression): string
    {
        return "<?php elseif ($expression) : ?>";
    }

    protected function compileElse(): string
    {
        return "<?php else : ?>";
    }

    protected function compileEndif(): string
    {
        return "<?php endif ?>";
    }

    protected function compileUnless(string $expression): string
    {
        return $this->compileIf("!($expression)");
    }

    protected function compileIsset(string $expression): string
    {
        return $this->compileIf("isset($expression)");
    }

    protected function compileEmpty(string $expression): string
    {
        return $this->compileIf("empty($expression)");
    }

    protected function compileFor(string $expression): string
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

    protected function compileForelse(string $expression): string
    {
        $offset = strpos($expression, ' ');
        $test = substr($expression, 0, (int) $offset);

        $this->directivesStack[] = 'forelse';
        return "<?php if (!empty($test)) : foreach($expression) : ?>";
    }

    protected function compileEndfor(): string
    {
        return '<?php endfor ?>';
    }

    protected function compileEndforeach(): string
    {
        return '<?php endforeach ?>';
    }

    protected function compileEndwhile(): string
    {
        return '<?php endwhile ?>';
    }

    protected function compileEndforelse(): string
    {
        return '<?php endforeach; endif ?>';
    }

    protected function compileContinue(?string $expression): string
    {
        if (null !== $expression) {
            return "<?php if ($expression) continue ?>";
        }

        return '<?php continue ?>';
    }

    protected function compileBreak(?string $expression): string
    {
        if (null !== $expression) {
            return "<?php if ($expression) break ?>";
        }

        return '<?php break ?>';
    }

    protected function compilePhp(?string $expression): string
    {
        if (null !== $expression) {
            return "<?php $expression ?>";
        }

        $this->directivesStack[] = 'php';
        return '<?php';
    }

    protected function compileEndphp(): string
    {
        return '?>';
    }

    protected function compileJson(string $expression): string
    {
        $parts = explode(',', $expression);
        $options = $parts[1] ?? 'JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT';
        $depth = $parts[2] ?? 512;

        return "<?= json_encode($parts[0], $options, $depth) ?>";
    }

    protected function compileTrim(): string
    {
        return '<?php ob_start() ?>';
    }

    protected function compileEndtrim(): string
    {
        return '<?= trim(ob_get_clean()) ?>';
    }

    protected function compileExtends(string $expression): string
    {
        return "<?php \$this->extends($expression) ?>";
    }

    protected function compileInclude(string $expression): string
    {
        return "<?php \$this->include($expression) ?>";
    }

    protected function compileOnce(?string $expression): string
    {
        $id = uniqid('once_');

        if (null !== $expression) {
            return "<?php if (!isset(\$this->section['$id']) : \$this->section['$id'] = true; echo htmlentities($expression); endif ?>";
        }

        $this->directivesStack[] = 'if';
        return "<?php if (!isset(\$this->section['$id']) : \$this->section['$id'] = true ?>";
    }

    protected function compileSection(string $expression): string
    {
        if (false === strpos($expression, ',')) {
            $this->directivesStack[] = 'section';
        }

        return "<?php \$this->section($expression) ?>";
    }

    protected function compileSectionExists(string $expression): string
    {
        return "<?php \$this->sectionExists($expression) ?>";
    }

    protected function compileSectionMissing(string $expression): string
    {
        return "<?php \$this->sectionMissing($expression) ?>";
    }

    protected function compileEndsection(): string
    {
        return "<?php \$this->endSection() ?>";
    }

    protected function compileShow(): string
    {
        return "<?php \$this->endSection(true) ?>";
    }

    protected function compileParent(): string
    {
        return "<!-- PARENT(<?php echo \$this->getCurrentSection() ?>) -->";
    }

    protected function compileYield(string $expression): string
    {
        return "<?= \$this->displaySection($expression) ?>";
    }

    protected function compileEnd(): string
    {
        while (null !== ($directive = array_pop($this->directivesStack))) {
            $directive = "end{$directive}";
            if ($this->isDirective($directive)) {
                return $this->callDirective($directive);
            }
        }

        throw new InvalidArgumentException('You should open a directive before closing it.');
    }

    public function setCacheDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        touch("$path/.touch");
        $this->cacheDirectory = $path;
    }

    public function flushCache(): void
    {
        if (isset($this->cacheDirectory)) {
            touch("{$this->cacheDirectory}/.touch");
        }
    }

    public function needsRebuild(string $name): bool
    {
        if (!isset($this->cacheDirectory)) {
            return true;
        }

        $touchFile = "{$this->cacheDirectory}/.touch";
        $cacheFile = $this->getCacheFile($name) ?? '';

        $touchTime = file_exists($touchFile) ? (int) filemtime($touchFile) : 0;
        $cacheTime = file_exists($cacheFile) ? (int) filemtime($cacheFile) : 0;

        if (!$this->debugMode) {
            return $touchFile > $cacheFile;
        }

        $templateFile = $this->getTemplateFile($name);
        return $touchTime > $cacheTime || filemtime($templateFile) > $cacheTime;
    }

    public function alias(string $alias, string $directory): void
    {
        if (isset($this->alias[$alias])) {
            throw new RuntimeException("Alias '$alias' is already defined.");
        }

        $this->alias[$alias] = $directory;
    }

    public function getCacheFile(string $name): ?string
    {
        if (isset($this->cacheDirectory)) {
            $filename = md5($name);
            $path = $this->debugMode ? 'dev' : 'prod';
            return "{$this->cacheDirectory}/$path/{$filename[0]}/{$filename}.php";
        }

        return null;
    }

    protected function getTemplateFile(string $name): string
    {
        $basepath = $this->workingDirectory;

        if (false !== ($offset = strpos($name, ':'))) {
            $alias = substr($name, 0, $offset);

            if (!isset($this->alias[$alias])) {
                throw new RuntimeException("Alias '$alias' is not defined.");
            }

            $basepath = $this->alias[$alias];
            $name = substr($name, $offset + 1);
        }

        return $basepath . '/' . ltrim($name, '/') . '.signal.php';
    }

    protected function acquireLock(string $filename, int $mode): mixed
    {
        $dir = dirname($filename);

        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create directory '$dir'.");
        }

        $handle = fopen($filename, 'w');

        if (!$handle) {
            throw new RuntimeException("Unable to create file '$filename'.");
        } elseif (!flock($handle, $mode)) {
            throw new RuntimeException('Unable to acquire ' . (($mode & LOCK_EX) ? 'exclusive' : 'shared') . "lock on file '$filename'.");
        }

        return $handle;
    }
}

(new Compiler(__DIR__))->compileString('Good Morning World!', __FILE__);
