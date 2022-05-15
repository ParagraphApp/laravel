<?php

namespace Paragraph;

use Illuminate\Foundation\Mix;
use Illuminate\Support\Str;
use Paragraph\Exceptions\FailedRequestException;

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

        if (is_callable($action['uses'])) {
            $closure = new \ReflectionFunction($action['uses']);
            $context = "Closure ".basename($closure->getFileName()).":{$closure->getStartLine()}";
        } else {
            $context = $action['uses'];
        }

        $client = $this->app[Client::class];

        $client->submitPage(
            $contents,
            $context,
            Client::PAGE_TYPE_WEB,
            WithParagraph::$currentPageName,
            WithParagraph::$currentSequenceName,
            WithParagraph::$currentState
        );

        $client->submitPlaceholders(
            array_map(function($text) {
                $text['visible'] = false;
                return $text;
            }, array_filter(Reader::texts(), fn($t) => ! preg_match('/\.blade\.php$/', $t['file'])))
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

        $this->submitPage($response->getContent(), $uri, 'GET');

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
