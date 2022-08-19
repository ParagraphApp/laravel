<?php

namespace Paragraph;

class Paragraph {
    public static function enableReader()
    {
        config([
            'paragraph.composer_enabled' => false,
            'paragraph.reader_enabled' => true
        ]);
    }

    public static function disableReader()
    {
        config([
            'paragraph.composer_enabled' => true,
            'paragraph.reader_enabled' => false
        ]);
    }

    public static function isComposerEnabled()
    {
        return (bool) config('paragraph.composer_enabled');
    }

    public static function isReaderEnabled()
    {
        return ! config('paragraph.composer_enabled') && config('paragraph.reader_enabled');
    }
}
