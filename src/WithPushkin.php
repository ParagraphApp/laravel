<?php

namespace Pushkin;

use Illuminate\Support\Str;
use Pushkin\Exceptions\FailedRequestException;

trait WithPushkin {
    public $currentPageName;

    public static $responseFragmentLength = 512;

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

        if (! $response->isOk()) {
            $fragment = substr($response->getContent(), 0, static::$responseFragmentLength);
            throw new FailedRequestException("Failed web request, code: {$response->getStatusCode()}, contents: {$fragment}");
        }

        $this->submitPage($response->getContent(), $uri, 'GET');

        return $response;
    }

    public function setUp(): void
    {
        parent::setUp();
        config(['app.url' => '']);
        app()->bind(TranslatorContract::class, Reader::class);
    }
}
