<?php

namespace Paragraph\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\View\Factory;
use Paragraph\Client;
use Paragraph\Commands\ClearRenderedViewsCommand;
use Paragraph\Commands\InitialiseCommand;
use Paragraph\Commands\SubmitRenderedViewsCommand;
use Paragraph\Hooks\MarkdownWrapper;
use Paragraph\Hooks\ProcessViewIfNecessary;
use Paragraph\Hooks\ViewFactoryWrapper;
use Paragraph\Mailer;
use Illuminate\Support\Facades\Blade;
use Paragraph\Commands\DownloadTextsCommand;
use Paragraph\Commands\SubmitTextsCommand;
use Paragraph\Commands\SubmitPageCommand;
use Paragraph\ProxyTranslator;
use Paragraph\Paragraph;
use Paragraph\Translator;
use Paragraph\TranslatorContract;
use Illuminate\Contracts\View\Factory as FactoryContract;

class ParagraphServiceProvider extends ServiceProvider {
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config/paragraph.php', 'paragraph');

        $openTagHandler = function($expression) {
            if (! $expression) {
                return "<?php \\Paragraph\\Paragraph::\$startLine = __LINE__; \$__env->startTranslation(); ?>";
            } else if ($expression[0] == '[') {
                return "<?php \\Paragraph\\Paragraph::\$startLine = __LINE__; \$__env->startTranslation($expression); ?>";
            }

            return "<?php echo app('translator')->get($expression); ?>";
        };

        $closeTagHandler = function() {
            return "<?php \\Paragraph\\Paragraph::\$endLine = __LINE__; echo \$__env->renderTranslation(); ?>";
        };

        Blade::directive('p', $openTagHandler);
        Blade::directive('endp', $closeTagHandler);
        Blade::directive('lang', $openTagHandler);
        Blade::directive('endlang', $closeTagHandler);

        $this->app['mail.manager']->extend('paragraph', function () {
            return new Mailer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                DownloadTextsCommand::class,
                SubmitTextsCommand::class,
                SubmitPageCommand::class,
                InitialiseCommand::class,
                ClearRenderedViewsCommand::class,
                SubmitRenderedViewsCommand::class,
            ]);
        }

        $this->app->singleton(Client::class, function ($app) {
            return new Client(config('paragraph.project_id'));
        });

        $this->app->singleton(Markdown::class, function ($app) {
            $config = $app->make('config');

            return new MarkdownWrapper($app->make('view'), [
                'theme' => $config->get('mail.markdown.theme', 'default'),
                'paths' => $config->get('mail.markdown.paths', []),
            ]);
        });

        $provider = new TranslationServiceProvider($this->app);
        $provider->register();

        $laravel = resolve('translator');
        $this->app->instance('translator', new ProxyTranslator($laravel));
        $boundViewFactory = $this->app['view'];

        if (! $boundViewFactory instanceof ViewFactoryWrapper) {
            (new ViewServiceProvider($this->app))->register();
        }

        View::composer('*', ProcessViewIfNecessary::class);

        View::macro('getFactory', function() {
            return $this->factory;
        });

        View::macro('getPath', function() {
            return $this->path;
        });

        Factory::macro('getRenderCount', function() {
            return $this->renderCount;
        });

        Paragraph::disableReader();
    }
}
