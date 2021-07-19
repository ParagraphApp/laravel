<?php

namespace Pushkin;

use Illuminate\Foundation\Mix;
use Illuminate\Support\Str;
use Pushkin\Exceptions\FailedRequestException;

trait WithPushkin {
    public static $currentPageName;

    public static $currentSequenceName;

    public static $currentState;

    public static $responseFragmentLength = 512;

    public static $currentlyInSequence = false;

    public function name($name)
    {
        WithPushkin::$currentPageName = $name;

        return $this;
    }

    public function state($state)
    {
        WithPushkin::$currentState = $state;

        return $this;
    }

    public function sequence($name, Callable $callable)
    {
        WithPushkin::$currentSequenceName = $name;
        WithPushkin::$currentlyInSequence = true;

        $callable();

        WithPushkin::$currentlyInSequence = false;
        WithPushkin::reset();
    }

    public static function reset()
    {
        WithPushkin::$currentSequenceName = null;
        WithPushkin::$currentState = null;
        WithPushkin::$currentPageName = null;
    }

    public function submitPage($contents, $uri, $method)
    {
        // Get controller name
        $action = $this->app['router']->getRoutes()->match(request()->create($uri, $method))->getAction();
        $context = $action['uses'];

        $this->app[Client::class]->submitPage(
            $contents,
            $context,
            Client::PAGE_TYPE_WEB,
            WithPushkin::$currentPageName,
            WithPushkin::$currentSequenceName,
            WithPushkin::$currentState
        );

        if (! WithPushkin::$currentlyInSequence) {
            WithPushkin::reset();
        }
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
            $fragment = substr($response->getContent(), 0, WithPushkin::$responseFragmentLength);
            throw new FailedRequestException("Failed web request, code: {$response->getStatusCode()}, contents: {$fragment}");
        }

        $this->submitPage($response->getContent(), $uri, 'GET');

        return $response;
    }

    public function setUp(): void
    {
        parent::setUp();
        config(['app.url' => '']);
        config(['mail.driver' => 'pushkin']);
        app()->bind(TranslatorContract::class, Reader::class);
        app()->bind(Mix::class, Mix::class);
        WithPushkin::reset();
    }
}
