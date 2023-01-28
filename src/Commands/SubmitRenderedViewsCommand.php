<?php

namespace Paragraph\Commands;

use Paragraph\Client;
use Paragraph\Storage\LaravelStorage;
use Illuminate\Console\Command;
use Paragraph\Storage\ViewStorage;

class SubmitRenderedViewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paragraph:submit-views';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit all available view snapshot(s)';

    /**
     * @var ViewStorage
     */
    protected $views;

    /**
     * @var Client
     */
    protected $client;

    public function __construct(ViewStorage $views, Client $client)
    {
        parent::__construct();

        $this->views = $views;
        $this->client = $client;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $views = $this->views->all();

        foreach ($views as $filename)
        {
            $this->line("Submitting {$filename}");
            $html = file_get_contents($filename);
            $css = $this->findCss($html);
            $assets = collect([]);

            if (count($css)) {
                $this->line("* Found " . count($css) . " referenced stylesheets");

                // Find and load them from local fs
                foreach ($css as $stylesheet) {
                    if (! file_exists(public_path($stylesheet))) {
                        $this->line("Skipping {$stylesheet} - unable to find it locally");
                        continue;
                    }

                    $assets->push([
                        'path' => $stylesheet,
                        'contents' => file_get_contents(public_path($stylesheet))
                    ]);
                }
            }

            $assets = $assets->unique();

            // Submit them via the API (maybe receive an id in the end?)
            $this->line("Submitting static assets necessary for rendering: " . $assets->count() . " files");

            $assets = $assets->map(function($asset) {
                return $this->client->submitAsset($asset['path'], $asset['contents']);
            });

            $this->client->submitPage($html, null, null, null, null, $assets->toArray());
        }

        $this->info("Submitted a total of " . count($views) . " snapshots");
    }

    protected function findCss($html)
    {
        preg_match_all('/<link.+?href="(?<css>[^"]+?\.css)/', $html, $matches);

        return collect($matches['css'] ?? [])
            ->map(function($link) { return parse_url($link); })
            ->filter(function($element) { return ! empty($element['path']); })
            ->map(function($element) { return $element['path']; })
            ->toArray();
    }
}
