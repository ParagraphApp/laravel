<?php

namespace Paragraph\Commands;

use Illuminate\Console\Command;
use Paragraph\Reader;
use Paragraph\TranslatorContract;
use Paragraph\WithParagraph;
use Tests\CreatesApplication;

class SubmitPageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paragraph:submit-page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Render and submit a page of choice';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        app()->bind(TranslatorContract::class, Reader::class);

        $url = $this->ask("Let's try to render one page, what URL should we try?", '/');
        $response = $this->render($url);
        $this->info("Received " . (strlen($response->getContent())) . " bytes of content, submitting to Pushkin");

        print_r($response->getContent());
    }

    protected function render($url)
    {
        $client = new class extends \Illuminate\Foundation\Testing\TestCase {
            use CreatesApplication, WithParagraph;

            public function setApp($application)
            {
                $this->app = $application;
            }
        };

        $client->setApp(app());

        return $client->get($url);
    }
}
