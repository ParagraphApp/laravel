<?php

namespace Pushkin;

use Pushkin\Client;
use Pushkin\LaravelStorage;
use Illuminate\Console\Command;

class DownloadTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushkin:download {--locale=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download new Pushkin translations and text updates';

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
        $client = resolve(Client::class);

        $translations = $client->downloadTranslations($this->option('locale'));
        $this->info("Fetched a total of " . count($translations) . " translations");

        LaravelStorage::saveTranslations($this->option('locale') ?: 'default', $translations);
    }
}
