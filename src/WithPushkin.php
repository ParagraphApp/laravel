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
        $client = $this->app[Client::class];

        $client->submitPage(
            $contents,
            $context,
            Client::PAGE_TYPE_WEB,
            WithPushkin::$currentPageName,
            WithPushkin::$currentSequenceName,
            WithPushkin::$currentState
        );

        $client->submitPlaceholders(
            array_map(function($text) {
                $text['visible'] = false;
                return $text;
            }, array_filter(Reader::texts(), fn($t) => ! preg_match('/\.blade\.php$/', $t['file'])))
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

        if ($response->isRedirection()) {
            return $this->get($response->headers->get('Location'), $headers);
        }

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
