<?php

namespace Pushkin;

use Illuminate\Support\Facades\Log;

class Storage {
    public function log($text, $file, $context, $signature = null, $location)
    {
        $line = json_encode(compact('text', 'file', 'context', 'signature', 'location'));
        $filename = $this::path();

        if (! file_exists($filename) || strpos(file_get_contents($filename), $line) === false) {
            file_put_contents($filename, $line . "\n",FILE_APPEND);
        }
    }

    /**
     * @return string
     */
    public static function path()
    {
        return storage_path('pushkin.log');
    }
}