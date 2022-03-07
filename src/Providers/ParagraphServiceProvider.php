<?php

namespace Paragraph\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Paragraph\Client;
use Paragraph\Commands\InitialiseCommand;
use Paragraph\Mailer;
use Illuminate\Support\Facades\Blade;
use Paragraph\Commands\DownloadTranslationsCommand;
use Paragraph\Commands\SubmitTextsCommand;
use Paragraph\Commands\SubmitPageCommand;
use Paragraph\ProxyTranslator;
use Paragraph\Translator;
use Paragraph\TranslatorContract;

class ParagraphServiceProvider extends ServiceProvider {
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config/paragraph.php', 'paragraph');

        $this->app->bind(TranslatorContract::class, Translator::class);

        Blade::directive('p', function($expression) {
            if (! $expression) {
                return "<?php \$pushkinStartLine = __LINE__; ob_start(); ?>";
            }

            return "<?php echo p($expression); ?>";
        });

        Blade::directive('endp', function() {
            return "<?php echo p(ob_get_clean(), \$pushkinStartLine, __LINE__); ?>";
        });

        $this->app['mail.manager']->extend('paragraph', function () {
            return new Mailer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                DownloadTranslationsCommand::class,
                SubmitTextsCommand::class,
                SubmitPageCommand::class,
                InitialiseCommand::class,
            ]);
        }

        $this->app->singleton(Client::class, function ($app) {
            return new Client(config('paragraph.project_id'));
        });

        $provider = new TranslationServiceProvider($this->app);
        $provider->register();

        $laravel = resolve('translator');
        $this->app->instance('translator', new ProxyTranslator($laravel));
    }
}
