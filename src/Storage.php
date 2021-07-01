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
     * @param $locale
     * @return array
     */
    public static function loadTranslations($locale = 'default')
    {
        if (! file_exists(storage_path("pushkin_{$locale}.json"))) return [];

        return json_decode(file_get_contents(storage_path("pushkin_{$locale}.json")), true);
    }

    public static function saveTranslations($locale, $translations)
    {
        file_put_contents(storage_path("pushkin_{$locale}.json"), json_encode($translations));
    }

    /**
     * @return string
     */
    public static function path()
    {
        return storage_path('pushkin.log');
    }
}
