<?php

namespace Pushkin;

use \Illuminate\Contracts\Translation\Translator;

class ProxyTranslator implements Translator {
    /**
     * @var Translator
     */
    protected $laravelTranslator;

    public function __construct($laravelTranslator)
    {
        $this->laravelTranslator = $laravelTranslator;
    }

    public function get($key, array $replace = [], $locale = null)
    {
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
