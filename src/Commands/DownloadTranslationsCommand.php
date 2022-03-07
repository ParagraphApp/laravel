<?php

namespace Paragraph\Commands;

use Paragraph\Client;
use Paragraph\Storage\LaravelStorage;
use Illuminate\Console\Command;

class DownloadTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paragraph:download {--locale=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download new Paragraph translations and text updates';

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

        $translations = $client->downloadTexts($this->option('locale'));
        $this->info("Fetched a total of " . count($translations) . " translations");

        LaravelStorage::saveTranslations($translations, $this->option('locale') ?: 'default');
    }
}
