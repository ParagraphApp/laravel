<?php

namespace Pushkin;

use Pushkin\Client;
use Pushkin\Storage;
use Illuminate\Console\Command;

class SubmitTextsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushkin:submit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit new texts found in the code';

    /**
     * @var Client
     */
    protected $client;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $contents = file_get_contents(Storage::path());
        $items = array_map(fn($v) => json_decode($v, true), explode("\n", $contents));
        $client = resolve(Client::class);

        $this->info("Processing a total of " . count($items) . " texts collected");

        if (! $client->submitTexts(array_filter($items))) {
            return 1;
        }

        unlink(Storage::path());
    }
}
