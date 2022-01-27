<?php

namespace Pushkin\Commands;

use Illuminate\Console\Command;
use Pushkin\Exceptions\ConfigurationFailure;

class InitialiseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushkin:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialise project with Pushkin';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->checkApiKey();

        $this->call('pushkin:submit-texts');
        $this->call('pushkin:submit-page');
    }

    protected function checkApiKey()
    {
        if (empty(config('pushkin.project_id'))) {
            throw new ConfigurationFailure("Missing Pushkin project id – make sure it's in your .env file or environment");
        }

        if (empty(config('pushkin.api_key'))) {
            throw new ConfigurationFailure("Missing Pushkin API key – make sure it's in your .env file or environment");
        }
    }
}
