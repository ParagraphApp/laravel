<?php

namespace Pushkin\Storage;

class LaravelStorage implements StorageContract {
    /**
     * @param $locale
     * @return array
     */
    public static function loadTranslations($locale = 'default')
    {
        if (! file_exists(storage_path("pushkin_{$locale}.json"))) return [];

        return json_decode(file_get_contents(storage_path("pushkin_{$locale}.json")), true);
    }

    public static function saveTranslations($locale = 'default', $translations)
    {
        file_put_contents(storage_path("pushkin_{$locale}.json"), json_encode($translations));
    }
}
