<?php

namespace Paragraph;

use GuzzleHttp\Client as Guzzle;

class Client {
    protected $client;

    protected $projectId;

    const PAGE_TYPE_WEB = 0;
    const PAGE_TYPE_EMAIL = 1;

    public function __construct()
    {
        $this->projectId = config('paragraph.project_id');

        $this->client = new Guzzle([
            'base_uri' => config('paragraph.api_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('paragraph.api_key')
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

        return json_decode($response->getBody(), true)['data'];
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
     * @param $texts
     * @return bool
     */
    public function submitTexts($texts)
    {
        $response = $this->client->put("{$this->projectId}/texts", [
            'json' => $texts
        ]);

        return true;
    }

    /**
     * @param $updates
     */
    public function updateProject($updates)
    {
        $response = $this->client->put("{$this->projectId}", [
            'json' => $updates
        ]);

        return true;
    }

    /**
     * @param $snapshot
     * @param $type
     * @param $name
     * @param null $sequence
     * @param null $state
     * @param array $assets
     * @return bool
     */
    public function submitPage($snapshot, $type = null, $name = null, $sequence = null, $state = null, $assets = [])
    {
        $response = $this->client->post("{$this->projectId}/pages", [
            'json' => array_filter(compact('snapshot', 'type', 'name', 'sequence', 'state', 'assets'), fn($v) => ! is_null($v))
        ]);

        return true;
    }
    /**
     * @param $snapshot
     * @param null $context
     * @param $type
     * @param $name
     * @param null $sequence
     * @param null $state
     * @return bool
     */
    public function submitAsset($path, $contents)
    {
        $response = $this->client->post("{$this->projectId}/assets", [
            'json' => compact('path', 'contents')
        ]);

        return json_decode($response->getBody(), true)['id'];
    }
}
