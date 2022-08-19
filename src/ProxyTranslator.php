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
     * @param string $key
     * @param array $replace
     * @param null $locale
     * @return mixed|string
     */
    public function get($key, array $replace = [], $locale = null)
    {
        $this->loadTextsIfNeeded($locale);
        $text = $this->laravelTranslator->get($key, $replace, $locale);

        if ($text != $key) {
            $existing = [
                'text' => $text,
                'locale' => $this->getLocale()
            ];
        }

        return p($key, null, null, $existing ?? null);
    }

    public function choice($key, $number, array $replace = [], $locale = null)
    {
        $this->loadTextsIfNeeded($locale);

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

    protected function loadTextsIfNeeded($locale)
    {
        $locale = $locale ?: $this->getLocale();

        if (! in_array($locale, $this->loaded)) {
            $texts = LaravelStorage::loadTranslations($locale);
            $this->transformTexts($texts, $locale);
        }
    }

    /**
     * @param $texts
     * @param $locale
     */
    protected function transformTexts($texts, $locale)
    {
        $normalised = [];

        foreach ($texts as $text) {
            $key = preg_match('/\./', $text['placeholder']) ? $text['placeholder'] : '*.' . $text['placeholder'];
            $normalised[$key] = $text['text'];
        }

        $this->laravelTranslator->addLines($normalised, $locale);

        $this->loaded[] = $locale;
    }
}
