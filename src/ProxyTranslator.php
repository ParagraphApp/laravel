<?php

namespace Paragraph;

use Illuminate\Contracts\Translation\Translator;
use Paragraph\Storage\LaravelStorage;

class ProxyTranslator implements Translator {
    /**
     * @var Translator
     */
    protected $laravelTranslator;

    protected $loaded = [];

    public function __construct($laravelTranslator)
    {
        $this->laravelTranslator = $laravelTranslator;
    }

    /**
     * @param $translations
     * @param $locale
     */
    protected function loadTranslations($translations, $locale)
    {
        $normalised = [];

        foreach ($translations as $translation) {
            $key = preg_match('/\./', $translation['placeholder']) ? $translation['placeholder'] : '*.' . $translation['placeholder']; 
            $normalised[$key] = $translation['text'];
        }

        $this->laravelTranslator->addLines($normalised, $locale);

        $this->loaded[] = $locale;
    }

    /**
     * @param string $key
     * @param array $replace
     * @param null $locale
     * @return mixed|string
     */
    public function get($key, array $replace = [], $locale = null)
    {
        $locale = $locale ?: $this->laravelTranslator->getLocale();

        if (! in_array($locale, $this->loaded)) {
            $translations = LaravelStorage::loadTranslations($locale);
            $this->loadTranslations($translations, $locale);
        }

        $translation = $this->laravelTranslator->get($key, $replace, $locale);

        return is_string($translation) ? p($translation) : $translation;
    }

    public function choice($key, $number, array $replace = [], $locale = null)
    {
        return $this->laravelTranslator->choice($key, $number, $replace, $locale);
    }

    public function getLocale()
    {
        return $this->laravelTranslator->getLocale();
    }

    public function setLocale($locale)
    {
        $this->laravelTranslator->setLocale($locale);
    }
}
