<?php

namespace Paragraph;

class Paragraph {
    protected static $composerEnabled = true;

    protected static $readerEnabled = false;

    public static $startLine;

    public static $endLine;

    public static function enableReader()
    {
        static::$composerEnabled = false;
        static::$readerEnabled = true;
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

    public static function isReaderEnabled()
    {
        return ! static::$composerEnabled && static::$readerEnabled;
    }

    public static function resetLines()
    {
        static::$startLine = null;
        static::$endLine = null;
    }
}
