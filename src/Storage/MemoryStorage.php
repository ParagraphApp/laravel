<?php

namespace Paragraph\Storage;

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
     * @param $texts
     * @param string $locale
     */
    public static function saveTranslations($texts)
    {
        foreach ($texts as $text) {
            if (! isset(static::$translations[$text['locale']])) {
                static::$translations[$text['locale']] = [];
            }

            static::$translations[$text['locale']][] = $text;
        }
    }
}
