<?php

namespace Paragraph\Commands;

use Paragraph\Client;
use Paragraph\Storage\LaravelStorage;
use Illuminate\Console\Command;
use Paragraph\Storage\ViewStorage;

class ClearRenderedViewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paragraph:clear-views {--y}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all current view snapshot(s)';

    /**
     * @var ViewStorage
     */
    protected $views;

    public function __construct(ViewStorage $views)
    {
        parent::__construct();

        $this->views = $views;
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
            unlink($filename);
            $this->line("Deleted {$filename}");
        }

        $this->info("Deleted a total of " . count($views) . " snapshots");
    }
}
