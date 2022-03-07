<?php

namespace Paragraph\Commands;

use Illuminate\Console\Command;
use Paragraph\Exceptions\ConfigurationFailure;

class InitialiseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paragraph:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialise project with Paragraph';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->checkApiKey();

        $this->call('paragraph:submit-texts');
        $this->call('paragraph:submit-page');
    }

    protected function checkApiKey()
    {
        if (empty(config('paragraph.project_id'))) {
            throw new ConfigurationFailure("Missing Paragraph project id – make sure it's in your .env file or environment");
        }

        if (empty(config('paragraph.api_key'))) {
            throw new ConfigurationFailure("Missing Paragraph API key – make sure it's in your .env file or environment");
        }
    }
}
