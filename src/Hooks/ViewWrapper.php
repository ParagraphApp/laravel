<?php

namespace Paragraph\Hooks;

use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Mail\Markdown;
use Paragraph\Paragraph;
use Paragraph\Storage\ViewStorage;

class ViewWrapper extends View {
    public function render(callable $callback = null)
    {
        $contents = parent::render($callback);

        if ( ! Paragraph::isReaderEnabled($this->name()) || $this->isAMarkdownView()) {
            return $contents;
        }

        Paragraph::disableReader();

        resolve(ViewStorage::class)->save($this->name(), $contents);

        return $this->stripDataTags($contents);
    }

    /**
     * @return bool
     */
    protected function isAMarkdownView()
    {
        $stack = debug_backtrace(1, 15);

        $lastCall = array_filter($stack, function($call) {
            return data_get($call, 'function') == 'buildMarkdownView';
        });
        
        $lastCall = array_pop($lastCall);

        return ! empty($lastCall) && data_get($lastCall, 'object.markdown') == $this->name();
    }

    protected function stripDataTags($html)
    {
        return preg_replace_callback('/<!-- paragraph-begin .+?(?=-->)-->(.+?(?=<!--))<!-- paragraph-end -->/s', function($matches) {
            return $matches[1];
        }, $html);
    }
}
