<?php

namespace Paragraph\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use Paragraph\Client;
use Paragraph\Exceptions\FailedParsing;
use Paragraph\WithParagraph;
use Tests\CreatesApplication;

class SubmitTextsCommand extends Command
{
    protected $expressions = [
        '/(?:trans|@choice|@lang|__|trans_choice)\s*\(\s*\'(.+?)(?<!\\\\)\'/',
        '/(?:trans|@choice|@lang|__|trans_choice)\s*\(\s*"(.+?)(?<!\\\\)"/',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paragraph:submit-texts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically discover and submit pages';

    protected $router;

    protected $ignoredFolders = ['vendor', 'storage', 'node_modules'];

    const PATH_OPTION_MANUAL = 'My template path is not in the list';
    const PATH_OPTION_ALL = 'All of the above';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        parent::__construct();

        $this->router = $router;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $routes = $this->routes();

        $langPath = $this->findPath('lang', 'language files');

        $texts = $this->parseLanguageFiles($langPath);

        $texts = $texts->reduce(function($carry, $file) {
            foreach ($file['texts'] as $key => $translation) {
                $source = trim(str_replace(base_path(''), '', $file['path']), DIRECTORY_SEPARATOR);

                if (is_string($translation)) {
                    $translation = [ $translation ];
                }

                if (is_array($translation)) {
                    $flat = Arr::dot($translation);

                    foreach ($flat as $subKey => $string) {
                        $carry->push([
                            'text' => $string,
                            'locale' => $file['locale'],
                            'source' => $source,
                            'placeholder' => [
                                'placeholder' => implode('.', array_filter([$file['key'], $subKey, $key]))
                            ]
                        ]);
                    }
                }
            }

            return $carry;
        }, collect([]));

        $viewsPath = $this->findPath('views', 'Blade templates');
        $views = $this->parseViewTemplates($viewsPath);

        $expanded = $routes->map(function($route) {
            $contents = file_get_contents($route['path']);
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

            try {
                $ast = $parser->parse($contents);
            } catch (Error $error) {
                echo "Parse error: {$error->getMessage()}\n";
                return;
            }

            $traverser = new NodeTraverser();
            $visitor = new class extends NodeVisitorAbstract {
                public $found = [];
                public $expectedMethod;
                protected $inside = false;

                public function setMethod($method) {
                    $this->expectedMethod = $method;
                    return $this;
                }

                public function enterNode(Node $node) {
                    if ($node instanceof Node\Stmt\ClassMethod) {
                        $this->inside = $this->expectedMethod == $node->name->name;
                    }

                    if ($node instanceof FuncCall && data_get($node, 'name.parts.0') == 'view' && $this->inside) {
                        foreach ($node->args as $argument) {
                            if ($argument->value instanceof String_) {
                                $this->found[] = $argument->value->value;
                            }
                        }
                    }
                }
            };

            $traverser->addVisitor($visitor->setMethod($route['method']));
            $traverser->traverse($ast);
            $route['views'] = $visitor->found;

            $parts = explode('\\', $route['context']);
            $route['name'] = end($parts);
            $route['name'] = preg_replace('/Controller@.+/i', '',  $route['name']);
            $route['name'] = ucwords($route['name'] . ' ' . $route['method']);

            return $route;
        })->filter(function($route) {
            return ! empty($route['views']);
        });

        $client = resolve(Client::class);
        $client->submitTexts($texts);

        $expanded->each(function($page) use ($client) {
             $client->submitPage(null, $page['context'], Client::PAGE_TYPE_WEB, $page['name']);
        });

        $texts = $views->reduce(function($carry, $view) {
            $contents = file_get_contents($view['path']);

            $this->extractStrings($contents)->each(function($string) use (&$carry, $view) {
                $carry->push(array_merge($string, [
                    'file' => trim(str_replace(base_path(''), '', $view['path']), DIRECTORY_SEPARATOR),
                    'visible' => false,
                    'key' => $view['key']
                ]));
            });

            return $carry;
        }, collect([]))->groupBy('key');

        $expanded = $expanded->map(function($route) use ($texts) {
            $route['texts'] = collect($route['views'])->reduce(function($carry, $view) use ($texts, $route) {
                if ($texts->has($view)) {
                    $carry[] = $texts->get($view)->map(function($text) use ($route) {
                        $text['context'] = $route['context'];
                        return $text;
                    });
                }

                return $carry;
            }, []);

            return $route;
        });

        $texts = $expanded->pluck('texts')->flatten(2);
        $this->line("Found {$texts->count()} texts in the view templates");

        $this->info("Sending to Paragraph");
        $client->submitPlaceholders($texts->toArray());
        $this->info("Done!");
    }

    protected function render($url)
    {
        $client = new class extends \Illuminate\Foundation\Testing\TestCase {
            use CreatesApplication, WithParagraph;

            public function setApp($application)
            {
                $this->app = $application;
            }
        };

        $client->setApp(app());

        return $client->get($url);
    }

    /**
     * @param $html
     * @return Collection
     */
    protected function extractStrings($html)
    {
        return collect($this->expressions)->reduce(function($carry, $expression) use ($html) {
            preg_match_all($expression, $html, $matches, PREG_OFFSET_CAPTURE);

            $matches = array_map(function($match) use ($html) {
                return [
                    'placeholder' => $match[0],
                    'location' => substr_count(mb_substr($html, 0, $match[1]), PHP_EOL) + 1
                ];
            }, $matches[1]);

            return $carry->concat($matches);
        }, collect([]));
    }

    protected function routes()
    {
        return collect($this->router->getRoutes())
            ->filter(function ($route) {
                return ! empty(array_intersect(['GET', 'POST'], $route->methods())) && $route->getActionName() != 'Closure';
            })
            ->map(function ($route) {
                list ($class, $method) = explode('@', $route->getActionName() . '@');

                return [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'context' => ltrim($route->getActionName(), '\\'),
                    'method' => $method,
                    'path' => (new \ReflectionClass($class))->getFileName()
                ];
            });
    }

    protected function parseViewTemplates($paths)
    {
        if (! is_array($paths)) {
            $paths = [$paths];
        }

        $views = collect([]);

        foreach ($paths as $path) {
            if (! file_exists($path) || ! is_dir($path)) {
                throw new FailedParsing("Path {$path} doesn't exist or isn't a folder");
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($iterator as $file) {
                if ($file->isDir() || ! preg_match('/\.blade\.php$/', $file->getPathname())) {
                    continue;
                }

                $key = str_replace($path, '', $file->getPathname());
                $key = ltrim($key, '//');
                $key = preg_replace('/\.blade\.php$/', '', $key);
                $key = str_replace(DIRECTORY_SEPARATOR, '.', $key);

                $views->push([
                    'key' => $key,
                    'path' => $file->getPathname()
                ]);
            }
        }

        $this->line("Discovered {$views->count()} view templates");

        return $views;
    }

    protected function parseLanguageFiles($paths)
    {
        if (! is_array($paths)) {
            $paths = [$paths];
        }

        $files = collect([]);

        foreach ($paths as $path) {
            if (! file_exists($path) || ! is_dir($path)) {
                throw new FailedParsing("Path {$path} doesn't exist or isn't a folder");
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($iterator as $file) {
                if ($file->isDir() || ! preg_match('/\.(?:php|json)$/', $file->getPathname())) {
                    continue;
                }

                $key = str_replace($path, '', $file->getPathname());
                $key = ltrim($key, '//');
                $key = preg_replace('/(?:\.php|\.json)$/', '', $key);
                $elements = explode(DIRECTORY_SEPARATOR, $key, 2);
                $locale = $elements[0];
                $key = end($elements);

                $texts = preg_match('/\.php$/', $file->getPathname()) ? require($file->getPathname()) : json_decode(file_get_contents($file->getPathname()), true);

                if (! is_array($texts)) {
                    throw new FailedParsing("File {$file->getPathname()} doesn't return an array with texts");
                }

                $files->push([
                    'key' => $key,
                    'locale' => $locale,
                    'path' => $file->getPathname(),
                    'texts' => $texts
                ]);
            }
        }

        $this->line("Discovered {$files->count()} language files with a total of {$files->pluck('texts')->flatten(1)->count()} texts");

        return $files;
    }

    /**
     * @param string $folder
     * @param string $noun
     * @return string
     */
    protected function findPath($folder, $noun)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path(), \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        $folders = [];

        foreach ($iterator as $file) {
            foreach ($this->ignoredFolders as $ignoredFolder) {
                if (strpos($file->getPathname(), base_path($ignoredFolder)) === 0) continue 2;
            }

            if ($file->isDir() && $file->getBasename() == $folder) {
                $folders[] = $file->getPathname();
            }
        }

        $folders[] = $this::PATH_OPTION_ALL;
        $folders[] = $this::PATH_OPTION_MANUAL;

        $viewsPath = $this->choice(
            "Where should we look for {$noun}?",
            $folders,
            0,
            null,
            true
        );

        if (in_array($this::PATH_OPTION_MANUAL, $viewsPath)) {
            return $this->ask("What's the correct path to the {$noun} folder?");
        }

        if (in_array($this::PATH_OPTION_ALL, $viewsPath)) {
            return array_filter($folders, fn($option) => ! in_array($option, [$this::PATH_OPTION_ALL, $this::PATH_OPTION_MANUAL]));
        }

        return $viewsPath;
    }
}
