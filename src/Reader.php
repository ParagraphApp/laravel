<?php

namespace Paragraph;

use Paragraph\Paragraph;
use PhpParser\Error;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\ParserFactory;
use Paragraph\Exceptions\FailedParsing;
use Paragraph\Storage\LaravelStorage;

class Reader {
    protected $input;

    protected $file;

    protected $stack;

    protected $source;

    protected $mode;

    protected $parser;

    protected $text;

    const MODE_HELPER_FUNCTION = 0;
    const MODE_DIRECTIVE = 1;

    protected static $currentPosition;

    protected static $currentIndex;

    protected static $placeholders = [];

    public function __construct($input, $text = null)
    {
        $this->input = $input;
        $this->text = $text;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    protected function getCurrentLineIndex($total)
    {
        if ($total == 1) return 0;

        return static::$currentIndex;
    }

    public function findSource()
    {
        $this->source = $this->readLines();

        if (preg_match_all('/ob_start\(\); \?>(.+?)(?=<\?php echo p\(ob_get_clean)/s', $this->source, $matches)) {
            $this->mode = $this::MODE_DIRECTIVE;
        } else if (preg_match_all('/ob_start\(\); \?>(.+?)(?=<\?php \\\Paragraph\\\Paragraph::\$endLine)/s', $this->source, $matches)) {
            $this->mode = $this::MODE_DIRECTIVE;
        } else if (preg_match_all('/startTranslation\(.*\); \?>(.+?)(?=<\?php \\\Paragraph\\\Paragraph::\$endLine)/s', $this->source, $matches)) {
            $this->mode = $this::MODE_DIRECTIVE;
        } else if (preg_match_all('/app\(\'translator\'\)->get\((.+?)(?=\))/', $this->source, $matches)) {
            $this->mode = $this::MODE_HELPER_FUNCTION;
        } else if (preg_match_all('/p\((.+?)(?=\))/', $this->source, $matches) || preg_match_all('/__\((.+?)(?=\))/', $this->source, $matches)) {
            $this->mode = $this::MODE_HELPER_FUNCTION;
        }

        if (is_null($this->mode)) {
            return;
        }

        $index = $this->getCurrentLineIndex(count($matches[1]));
        if (! isset($matches[1][$index])) throw new FailedParsing("Unable to locate call #{$index}");

        return [
            'placeholder' => trim($this->input),
            'file' => $this->humanReadableFilePath(),
            'context' => $this->context(),
            'location' => Paragraph::$startLine,
            'text' => $this->text
        ];
    }

    /**
     * @return string
     */
    protected function humanReadableFilePath()
    {
        $basePath = function_exists('base_path') ? base_path() : dirname(dirname(__FILE__));

        if (preg_match('/storage\/framework\/views/', $this->file)) {
            preg_match('/\*\*PATH (.+\.blade\.php) ENDPATH\*\*/', file_get_contents($this->file), $matches);

            return trim(str_replace($basePath, '', $matches[1]), DIRECTORY_SEPARATOR);
        }

        return trim(str_replace($basePath, '', $this->file), DIRECTORY_SEPARATOR);
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
                $name = request()->route()->getActionName();

                if ($name == 'Closure') {
                    $name .= ' ' . implode('|', request()->route()->methods) . ' ' . request()->route()->uri;
                }

                return $name;
            }
        }
    }

    protected function findActualFile()
    {
        $this->stack = debug_backtrace();
        $currentFolder = dirname(__FILE__);

        $this->stack = array_filter($this->stack, function($call) use ($currentFolder) {
            return isset($call['file']) && strpos($call['file'], dirname($currentFolder)) !== 0 && ! preg_match('/laravel\/framework/', $call['file']);
        });

	    $this->stack = array_values($this->stack);

        if (preg_match('/ManagesTranslations\.php$/', $this->stack[0]['file'])) {
            array_shift($this->stack);
        }

        $this->setFile($this->stack[0]['file']);

        if (! Paragraph::$startLine) {
            Paragraph::$startLine = $this->stack[0]['line'];
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

    public function tag()
    {
        $data = $this->findSource();
        Paragraph::resetLines();

        static::$placeholders[] = $data;

        $prefix = "<!-- paragraph-begin " . json_encode($data) . " -->";
        $postfix = "<!-- paragraph-end -->";

        return new Text($prefix . ($this->text['compiled'] ?? $this->input) . $postfix);
    }

    /**
     * @return array
     */
    public static function placeholders()
    {
        return static::$placeholders;
    }

    /**
     * @return string
     */
    protected function readLines()
    {
        if (! $this->file) $this->findActualFile();
        $lines = file($this->file);

        $count = Paragraph::$endLine ? Paragraph::$endLine - Paragraph::$startLine + 1 : 1;
        $currentPosition = implode(':', [$this->file, Paragraph::$startLine]);

        if ($currentPosition == static::$currentPosition) {
            static::$currentIndex++;
        } else {
            static::$currentPosition = $currentPosition;
            static::$currentIndex = 0;
        }

        return implode('', array_slice($lines, Paragraph::$startLine - 1, $count));
    }
}
