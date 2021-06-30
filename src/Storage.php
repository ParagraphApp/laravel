<?php

namespace Pushkin;

use Illuminate\Support\Facades\Log;

class Storage {
    public function log($data)
    {
        $line = json_encode($data);
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
