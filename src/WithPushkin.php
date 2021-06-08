<?php

namespace Pushkin;

use Illuminate\Support\Str;

trait WithPushkin {
    public function submitPage($contents, $uri, $method)
    {
        // Get controller name
        $action = $this->app['router']->getRoutes()->match(request()->create($uri, $method))->getAction();
        $context = $action['uses'];

        $input = "/tmp/" . Str::random(126);
        $output = "/tmp/" . Str::random(126);

        file_put_contents($input, $contents);
        exec("python3.8 " . resource_path('tools/web.py') . " {$input} > {$output} 2>/dev/null");
        $contents = file_get_contents($output);
        unlink($input);
        unlink($output);

        // Generate inline HTML
        $this->app[Client::class]->submitPage($contents, $context);
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
}