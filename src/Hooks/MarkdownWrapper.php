<?php

namespace Paragraph\Hooks;

use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Mail\Markdown;
use Paragraph\Paragraph;
use Paragraph\Storage\ViewStorage;

class MarkdownWrapper extends Markdown {
    public function render($view, array $data = [], $inliner = null)
    {
        $contents = parent::render($view, $data, $inliner);

        if ( ! Paragraph::isReaderEnabled($view)) {
            return $contents;
        }

        Paragraph::disableReader();

        resolve(ViewStorage::class)->save($view, $contents);

        return $this->stripDataTags($contents);
    }

    protected function name()
    {
        return $this->view->name();
    }

    protected function stripDataTags($html)
    {
        return preg_replace_callback('/<!-- paragraph-begin .+?(?=-->)-->(.+?(?=<!--))<!-- paragraph-end -->/s', function($matches) {
            return $matches[1];
        }, $html);
    }
}
