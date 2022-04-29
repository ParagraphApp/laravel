<?php

namespace Paragraph\Storage;

class LaravelStorage implements StorageContract {
    /**
     * @param $locale
     * @return array
     */
    public static function loadTranslations($locale = 'default')
    {
        $path = storage_path("paragraph_{$locale}.json");

        if (! file_exists($path) && ! $path = static::fallback($locale)) return [];

        return json_decode(file_get_contents($path), true);
    }

    /**
     * @param $locale
     * @return string
     */
    protected static function fallback($locale)
    {
        $files = scandir(dirname(storage_path("paragraph.json")));

        $matches = array_filter($files, function($path) use ($locale) {
            return preg_match('/^paragraph_'.$locale.'_[A-Z]{2}\.json$/', basename($path));
        });

        if (count($matches)) {
            return storage_path(array_values($matches)[0]);
        }
    }

    public static function saveTranslations($texts)
    {
        $locales = [];

        foreach ($texts as $text) {
            $localeKey = $text['locale'];

            if (! isset($locales[$localeKey])) {
                $locales[$localeKey] = [];
            }

            $locales[$localeKey][] = $text;
        }

        foreach ($locales as $key => $texts) {
            file_put_contents(storage_path("paragraph_{$key}.json"), json_encode($texts));
        }
    }
}
