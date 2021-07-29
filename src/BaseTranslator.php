<?php

namespace Pushkin;

use PhpParser\Error;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\ParserFactory;
use Pushkin\Storage\LaravelStorage;

abstract class BaseTranslator {
    protected $input;

    protected $storage;

    protected $file;

    protected $stack;

    protected $startLine;

    protected $endLine;

    protected $source;

    protected $mode;

    protected $parser;

    const MODE_HELPER_FUNCTION = 0;
    const MODE_DIRECTIVE = 1;

    public function __construct($input, $startLine = null, $endLine = null)
    {
        $this->input = $input;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->storage = new LaravelStorage();
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function findSource()
    {
        $this->source = $this->readLines();

        if (preg_match('/ob_start\(\); \?>(.+?)(?=<\?php echo p\(ob_get_clean)/s', $this->source, $matches)) {
            $this->mode = $this::MODE_DIRECTIVE;
            $clean = $matches[1];
        } else if (preg_match('/p\((.+?)(?=\);)/', $this->source, $matches)) {
            $this->mode = $this::MODE_HELPER_FUNCTION;
            $clean = "<?php " . $matches[1] . ";";
        }

        if (is_null($this->mode)) return;

        try {
            $ast = $this->parser->parse($clean);
            $signature = $this->findSignature($ast);
        } catch (Error $error) {
            //dd("Parse error: {$error->getMessage()}\n");
        }

        return [
            'text' => trim($this->input),
            'file' => $this->getCurrentFilePath(),
            'context' => $this->context(),
            'signature' => $signature ?? null,
            'location' => $this->startLine
        ];
    }

    /**
     * @return array|string|string[]
     */
    protected function getCurrentFilePath()
    {
        $currentFolder = dirname(__FILE__);
        $stack = debug_backtrace();

        $stack = array_filter($stack, function($call) use ($currentFolder) {
            return isset($call['file']) && strpos($call['file'], $currentFolder) !== 0;
        });

        array_shift($stack);
        $lastCall = array_shift($stack);

        $basePath = function_exists('base_path') ? base_path() : dirname(dirname(__FILE__));

        if (preg_match('/storage\/framework\/views/', $lastCall['file'])) {
            preg_match('/\*\*PATH (.+\.blade\.php) ENDPATH\*\*/', file_get_contents($lastCall['file']), $matches);

            return str_replace($basePath, '', $matches[1]);
        }

        return str_replace($basePath, '', $lastCall['file']);
    }

    /*
     * @return string
     */
    public function findSignature(array $nodes)
    {
        if ($this->mode == $this::MODE_DIRECTIVE) {
            $variableCount = 0;

            return implode('', array_filter(array_map(function($node) use (&$variableCount) {
                if ($node instanceof InlineHTML) {
                    return $node->value;
                }

                if ($node instanceof Echo_) {
                    return "{variable".++$variableCount."}";
                }
            }, $nodes)));
        }

        if (! $nodes[0]->expr instanceof Encapsed) {
            return;
        }

        return implode('', array_map(function($part) {
            if ($part instanceof EncapsedStringPart) return $part->value;
            if ($part instanceof Variable) return "{".$part->name."}";
        }, $nodes[0]->expr->parts));
    }

    /**
     * @return string
     */
    protected function context()
    {
        if (! empty($this->stack)) {
            $lastCall = array_filter($this->stack, function($call) {
                return data_get($call, 'function') == 'buildMarkdownView';
            });

            if (! empty($lastCall)) {
                $lastCall = array_shift($lastCall);
                return get_class($lastCall['object']);
            }
        }

        if (function_exists('request')) {
            if (request() && request()->route()) {
                return request()->route()->getActionName();
            }
        }
    }

    protected function findActualFile()
    {
        $this->stack = debug_backtrace();
        $currentFolder = dirname(__FILE__);

        $this->stack = array_filter($this->stack, function($call) use ($currentFolder) {
            return isset($call['file']) && strpos($call['file'], $currentFolder) !== 0;
        });

        array_shift($this->stack);

        $this->file = $this->stack[0]['file'];

        if (! $this->startLine) {
            $this->startLine = $this->stack[0]['line'];
        }
    }

    /**
     * @param $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string
     */
    protected function readLines()
    {
        if (! $this->file) $this->findActualFile();
        $lines = file($this->file);

        $count = $this->endLine ? $this->endLine - $this->startLine + 1 : 1;

        return implode("\n", array_slice($lines, $this->startLine - 1, $count));
    }
}
