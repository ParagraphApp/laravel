<?php

namespace Paragraph;

use Illuminate\Foundation\Mix;
use Illuminate\Support\Str;
use Paragraph\Exceptions\FailedRequestException;
use Paragraph\Storage\ViewStorage;

trait WithParagraph {
    public static $currentPageName;

    public static $currentSequenceName;

    public static $currentState;

    public static $responseFragmentLength = 512;

    public static $currentlyInSequence = false;

    public function name($name)
    {
        WithParagraph::$currentPageName = $name;

        return $this;
    }

    public function state($state)
    {
        WithParagraph::$currentState = $state;

        return $this;
    }

    public function sequence($name, Callable $callable)
    {
        WithParagraph::$currentSequenceName = $name;
        WithParagraph::$currentlyInSequence = true;

        $callable();

        WithParagraph::$currentlyInSequence = false;
        WithParagraph::reset();
    }

    public static function reset()
    {
        WithParagraph::$currentSequenceName = null;
        WithParagraph::$currentState = null;
        WithParagraph::$currentPageName = null;
    }

    public function submitPage($contents, $uri, $method)
    {
        // Get controller name
        $action = $this->app['router']->getRoutes()->match(request()->create($uri, $method))->getAction();
        $client = $this->app[Client::class];

        $client->submitPage(
            $contents,
            null,
            Client::PAGE_TYPE_WEB,
            WithParagraph::$currentPageName,
            WithParagraph::$currentSequenceName,
            WithParagraph::$currentState
        );

        if (! WithParagraph::$currentlyInSequence) {
            WithParagraph::reset();
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
            $fragment = substr($response->getContent(), 0, WithParagraph::$responseFragmentLength);
            throw new FailedRequestException("Failed web request, code: {$response->getStatusCode()}, contents: {$fragment}");
        }

        if (file_exists(ViewStorage::$lastSavedSnapshot)) {
            $this->submitPage(file_get_contents(ViewStorage::$lastSavedSnapshot), $uri, 'GET');
        }

        return $response;
    }

    public function setUp(): void
    {
        parent::setUp();
        config(['app.url' => '']);
        config(['mail.driver' => 'paragraph']);
        app()->bind(TranslatorContract::class, Reader::class);
        app()->bind(Mix::class, Mix::class);
        WithParagraph::reset();
    }
}
