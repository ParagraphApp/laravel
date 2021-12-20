<?php

namespace Pushkin\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Pushkin\Client;
use Pushkin\Mailer;
use Illuminate\Support\Facades\Blade;
use Pushkin\Commands\DownloadTranslationsCommand;
use Pushkin\Commands\SubmitPagesCommand;
use Pushkin\ProxyTranslator;
use Pushkin\Translator;
use Pushkin\TranslatorContract;

class PushkinServiceProvider extends ServiceProvider implements DeferrableProvider {
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config/pushkin.php', 'pushkin');

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

        $this->app['mail.manager']->extend('pushkin', function () {
            return new Mailer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                DownloadTranslationsCommand::class,
                SubmitPagesCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client(config('pushkin.project_id'));
        });

        $this->app->instance('translator.laravel', $this->app['translator']);

        $this->app->singleton('translator', function ($app) {
            return new ProxyTranslator($app['translator.laravel']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['translator'];
    }
}
