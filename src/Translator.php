<?php

namespace Pushkin;

class Translator extends BaseTranslator implements TranslatorContract {
    /**
     * @var array
     */
    public static $translations = [];

    public function translate()
    {
        $this->loadLocaleTranslations(app()->getLocale());
        $location = $this->findSource();

        return $this->findTranslation($this->input, $location['file'], app()->getLocale()) ?: $this->input;
    }

    /**
     * @param $text
     * @param $file
     * @param $locale
     * @return string
     */
    protected function findTranslation($text, $file, $locale)
    {
        $match = collect(data_get(static::$translations, $locale, []))->first(function($translation) use ($text, $file) {
            return $translation['file'] == $file && $translation['original_version'] == $text;
        });

        if ($match) return $match['text'];
    }

    protected function loadLocaleTranslations($locale)
    {
        $translations = Storage::loadTranslations($locale);

        static::$translations[$locale] = empty($translations) ? Storage::loadTranslations() : $translations;
    }
}
