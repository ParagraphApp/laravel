<?php

namespace Pushkin;

use PhpParser\Error;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\ParserFactory;

abstract class BaseTranslator {
    protected $input;

    protected $storage;

    protected $file;

    protected $startLine;

    protected $endLine;

    protected $source;

    public function __construct($input, $file, $startLine = null, $endLine = null)
    {
        $this->input = $input;
        $this->file = $file;
        $this->startLine = $startLine;
        $this->endLine = $endLine;

        $this->storage = new Storage();
    }

    public function findSource()
    {
        $this->source = $this->readLine($this->file, $this->startLine, $this->endLine - $this->startLine + 1);
        preg_match('/ob_start\(\); \?>(.+?)(?=<\?php echo p\(ob_get_clean)/s', $this->source, $matches);
        $clean = $matches[1];

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        try {
            $ast = $parser->parse($clean);
            $signature = $this->findSignature($ast);
        } catch (Error $error) {
            //echo "Parse error: {$error->getMessage()}\n";
            //return;
        }

        return [
            'text' => $this->input,
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

        $basePath = function_exists('base_path') ? base_path() : '';

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
        $variableCount = 0;

        return implode(' ', array_filter(array_map(function($node) use (&$variableCount) {
            if ($node instanceof InlineHTML) {
                return trim($node->value);
            }

            if ($node instanceof Echo_) {
                return "{variable".++$variableCount."}";
            }
        }, $nodes)));

        $functionCall = array_filter($node, function($child) {
            return $child instanceof Echo_ && $child->exprs[0]->name->parts[0] == 'p';
        });
        if (empty($functionCall)) return;

        $functionCall = array_shift($functionCall);
        $functionCall = $functionCall->exprs[0];

        if (! $functionCall->args[0]->value instanceof Encapsed) {
            return;
        }

        return implode('', array_map(function($part) {
            if ($part instanceof EncapsedStringPart) return $part->value;
            if ($part instanceof Variable) return "{".$part->name."}";
        }, $functionCall->args[0]->value->parts));
    }

    /**
     * @return string
     */
    protected function context()
    {
        $currentFolder = dirname(__FILE__);
        $stack = debug_backtrace();
        $stack = array_filter($stack, function($call) use ($currentFolder) {
            return isset($call['file']) && strpos($call['file'], $currentFolder) !== 0;
        });

        array_shift($stack);

        $lastCall = array_filter($stack, function($call) {
            return data_get($call, 'function') == 'buildMarkdownView';
        });

        if (! empty($lastCall)) {
            $lastCall = array_shift($lastCall);
            return get_class($lastCall['object']);
        }

        if (function_exists('request')) {
            if (request() && request()->route()) {
                return request()->route()->getActionName();
            }
        }
    }

    /**
     * @param $file
     * @param $lineNo
     * @param int $count
     * @return string
     */
    protected function readLine($file, $start, $count = 1)
    {
        $lines = file($file);

        return implode("\n", array_slice($lines, $start - 1, $count));
    }
}
