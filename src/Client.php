<?php

namespace Pushkin;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;

class Client {
    protected $client;

    protected $projectId;

    const PAGE_TYPE_WEB = 0;
    const PAGE_TYPE_EMAIL = 1;

    public function __construct($projectId)
    {
        $this->projectId = $projectId;

        $this->client = new Guzzle([
            'base_uri' => config('services.pushkin.api_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.pushkin.api_key')
            ]
        ]);
    }

    /**
     * @param $texts
     * @return bool
     */
    public function submitTexts($texts)
    {
        try {
            $response = $this->client->post("{$this->projectId}/texts", [
                'json' => $texts
            ]);

            return true;
        } catch (RequestException $e) {
        }
    }

    /**
     * @param $contents
     * @param $context
     * @param $type
     * @return bool
     */
    public function submitPage($contents, $context, $type = Client::PAGE_TYPE_WEB)
    {
        try {
            $response = $this->client->post("{$this->projectId}/pages", [
                'json' => [
                    'snapshot' => $contents,
                    'context' => $context,
                    'type' => $type
                ]
            ]);

            return true;
        } catch (RequestException $e) {
        }
    }
}