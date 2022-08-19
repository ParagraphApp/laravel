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
            $this->client->submitPage(
                file_get_contents($filename)
            );
        }

        $this->info("Submitted a total of " . count($views) . " snapshots");
    }
}
