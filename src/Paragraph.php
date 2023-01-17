<?php

namespace Paragraph;

use Illuminate\Support\Facades\Log;

class Paragraph {
    protected static $composerEnabled = true;

    protected static $readerEnabled = false;

    public static $startLine;

    public static $endLine;

    public static $currentViewName;

    public static function view()
    {
        return static::$currentViewName;
    }

    public static function enableReader($viewName = null)
    {
        static::$composerEnabled = false;
        static::$readerEnabled = true;

        if ($viewName) {
            static::$currentViewName = $viewName;
            Log::info("Enabling Paragraph reader for {$viewName}");
        }
    }

    public static function disableReader()
    {
        static::$composerEnabled = true;
        static::$readerEnabled = false;
    }

    public static function isComposerEnabled()
    {
        return static::$composerEnabled;
    }

    public static function isReaderEnabled($viewName = null)
    {
        if ($viewName && $viewName != static::$currentViewName) {
            return false;
        }

        return ! static::$composerEnabled && static::$readerEnabled;
    }

    public static function resetLines()
    {
        static::$startLine = null;
        static::$endLine = null;
    }
}
