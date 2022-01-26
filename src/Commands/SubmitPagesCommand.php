<?php

namespace Pushkin\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use Psy\Util\Str;
use Pushkin\Client;
use Pushkin\Exceptions\FailedParsing;
use Pushkin\Reader;
use Pushkin\TranslatorContract;
use Pushkin\WithPushkin;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Tests\CreatesApplication;

class SubmitPagesCommand extends Command
{
    protected $expressions = [
        '/@lang\(\'(.+?)\'\)/',
        '/@lang\("(.+?)"\)/',
        '/__\(\'(.+?)\'\)/',
        '/__\("(.+?)"\)/',
        '/(?:@choice|trans_choice)\("(.+?)",/',
        '/(?:@choice|trans_choice)\(\'(.+?)\',/'
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushkin:submit-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically discover and submit pages';

    protected $router;

    protected $ignoredFolders = ['vendor', 'storage', 'node_modules'];

    const PATH_OPTION_MANUAL = 'My template path is not in the list';

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
        $viewsPath = $this->findViewsPath();
        $views = $this->views($viewsPath);

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

        $expanded->each(function($page) use ($client) {
             $client->submitPage(null, $page['context'], Client::PAGE_TYPE_WEB, $page['name']);
        });

        $texts = $views->reduce(function($carry, $view) {
            $contents = file_get_contents($view['path']);

            $this->extractStrings($contents)->each(function($string) use (&$carry, $view) {
                $carry->push(array_merge($string, [
                    'file' => str_replace(base_path(''), '', $view['path']),
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

        $this->info("Sending to Pushkin");
        $client->submitTexts($texts->toArray());
        $this->info("Done!");

        app()->bind(TranslatorContract::class, Reader::class);
        $url = $this->ask("Now let's try to render one page, what URL should we try?", '/');
        $response = $this->render($url);
        $this->info("Received " . (strlen($response->getContent())) . " bytes of content, submitting to Pushkin");
        print_r($response->getContent());
    }

    protected function render($url)
    {
        $client = new class extends \Illuminate\Foundation\Testing\TestCase {
            use CreatesApplication, WithPushkin;

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
                    'text' => $match[0],
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
                list ($class, $method) = explode('@', $route->getActionName());

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

    protected function views($path)
    {
        if (! file_exists($path) || ! is_dir($path)) {
            throw new FailedParsing("Path {$path} doesn't exist or isn't a folder");
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $views = collect([]);

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

        $this->line("Discovered {$views->count()} view templates");

        return $views;
    }

    /**
     * @return string
     */
    protected function findViewsPath()
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

            if ($file->isDir() && $file->getBasename() == 'views') {
                $folders[] = $file->getPathname();
            }
        }

        $folders[] = $this::PATH_OPTION_MANUAL;

        $viewsPath = $this->choice(
            'Where should we look for Blade templates?',
            $folders,
            0
        );

        if ($viewsPath == $this::PATH_OPTION_MANUAL) {
            return $this->ask("What's the correct path to the template folder?");
        }

        return $viewsPath;
    }
}
