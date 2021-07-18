<?php

namespace Pushkin\Storage;

class MemoryStorage implements StorageContract {
    protected static $translations = [];

    /**
     * @param $locale
     * @return array
     */
    public static function loadTranslations($locale = 'default')
    {
        return static::$translations[$locale] ?? [];
    }

    /**
     * @param string $locale
     * @param $translations
     */
    public static function saveTranslations($locale = 'default', $translations)
    {
        static::$translations[$locale] = $translations;
    }
}
