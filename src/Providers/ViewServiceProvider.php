<?php

namespace Paragraph\Providers;

use Illuminate\Contracts\View\Factory as FactoryContract;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Factory;
use Paragraph\Hooks\ViewFactoryWrapper;

class ViewServiceProvider extends \Illuminate\View\ViewServiceProvider
{
    public function register()
    {
        $this->registerFactory();
    }
    
    protected function createFactory($resolver, $finder, $events)
    {
        return new ViewFactoryWrapper($resolver, $finder, $events);
    }
}
