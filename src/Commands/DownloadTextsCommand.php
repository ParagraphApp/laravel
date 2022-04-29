<?php

namespace Paragraph\Commands;

use Paragraph\Client;
use Paragraph\Storage\LaravelStorage;
use Illuminate\Console\Command;

class DownloadTextsCommand extends Command
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
    protected $description = 'Download current Paragraph texts';

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

        $texts = $client->downloadTexts($this->option('locale'));
        $this->info("Fetched a total of " . count($texts) . " texts");

        LaravelStorage::saveTranslations($texts);
    }
}
