<?php

namespace Paragraph\Hooks;

use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Paragraph\Paragraph;
use Paragraph\Storage\ViewStorage;

class ViewWrapper extends View {
    public function render(callable $callback = null)
    {
        $contents = parent::render($callback);

        if ( ! Paragraph::isReaderEnabled($this->name())) {
            return $contents;
        }

        Paragraph::disableReader();

        resolve(ViewStorage::class)->save($this->name(), $contents);

        return $this->stripDataTags($contents);
    }

    protected function stripDataTags($html)
    {
        Log::info("Removing Paragraph tags from {$this->name()}");

        return preg_replace_callback('/<!-- paragraph-begin .+?(?=-->)-->(.+?(?=<!--))<!-- paragraph-end -->/s', function($matches) {
            return $matches[1];
        }, $html);
    }
}
