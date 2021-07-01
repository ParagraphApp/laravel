<?php

namespace Pushkin;

use Illuminate\Support\Str;

trait WithPushkin {
    public $currentPageName;

    public function name($name)
    {
        $this->currentPageName = $name;

        return $this;
    }

    public function submitPage($contents, $uri, $method)
    {
        // Get controller name
        $action = $this->app['router']->getRoutes()->match(request()->create($uri, $method))->getAction();
        $context = $action['uses'];

        $this->app[Client::class]->submitPage($contents, $context, Client::PAGE_TYPE_WEB, $this->currentPageName);
    }

    /**
     * @param $uri
     * @param array $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function get($uri, array $headers = [])
    {
        $response = parent::get($uri, $headers);

        if ($response->isOk()) {
            $this->submitPage($response->getContent(), $uri, 'GET');
        }

        return $response;
    }

    public function setUp(): void
    {
        parent::setUp();
        config(['app.url' => '']);
        app()->bind(TranslatorContract::class, Reader::class);
    }
}
