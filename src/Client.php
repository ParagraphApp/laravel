<?php

namespace Pushkin;

use GuzzleHttp\Client as Guzzle;

class Client {
    protected $client;

    protected $projectId;

    const PAGE_TYPE_WEB = 0;
    const PAGE_TYPE_EMAIL = 1;

    public function __construct()
    {
        $this->projectId = config('pushkin.project_id');

        $this->client = new Guzzle([
            'base_uri' => config('pushkin.api_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('pushkin.api_key')
            ]
        ]);
    }

    /**
     * @param string $locale
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function downloadTexts($locale = null)
    {
        $response = $this->client->get("{$this->projectId}/texts", [
            'query' => array_filter([
                'locale' => $locale
            ])
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @param $texts
     * @return bool
     */
    public function submitPlaceholders($texts)
    {
        $response = $this->client->post("{$this->projectId}/placeholders", [
            'json' => $texts
        ]);

        return true;
    }

    /**
     * @param $snapshot
     * @param $context
     * @param $type
     * @param $name
     * @param null $sequence
     * @param null $state
     * @return bool
     */
    public function submitPage($snapshot, $context, $type = Client::PAGE_TYPE_WEB, $name = 'Untitled', $sequence = null, $state = null)
    {
        $response = $this->client->post("{$this->projectId}/pages", [
            'json' => compact('snapshot', 'context', 'type', 'name', 'sequence', 'state')
        ]);

        return true;
    }
}
