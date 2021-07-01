<?php

namespace Pushkin\Providers;

use Illuminate\Support\ServiceProvider;
use Pushkin\Client;
use Pushkin\Mailer;
use Illuminate\Support\Facades\Blade;
use Pushkin\DownloadTranslationsCommand;
use Pushkin\Translator;
use Pushkin\TranslatorContract;

class PushkinServiceProvider extends ServiceProvider {
    public function boot()
    {
        $this->app->bind(TranslatorContract::class, Translator::class);

        Blade::directive('p', function($expression) {
            if (! $expression) {
                return "<?php ob_start(); ?>";
            }

            return "<?php echo p($expression); ?>";
        });

        Blade::directive('endp', function() {
            return "<?php echo p(ob_get_clean()); ?>";
        });

        $this->app['mail.manager']->extend('pushkin', function () {
            return new Mailer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                DownloadTranslationsCommand::class
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client(config('services.pushkin.project_id'));
        });
    }
}
