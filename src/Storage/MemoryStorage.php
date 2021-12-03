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
     * @param $translations
     * @param string $locale
     */
    public static function saveTranslations($translations, $locale = 'default')
    {
        static::$translations[$locale] = $translations;
    }
}
