<?php

namespace Pushkin;

use Pushkin\Storage\LaravelStorage;
use Pushkin\Storage\StorageContract;

class Translator extends BaseTranslator implements TranslatorContract {
    /**
     * @var array
     */
    public static $translations = [];

    public $locale;

    public $storage;

    public function __construct($input, $startLine = null, $endLine = null)
    {
        parent::__construct($input, $startLine, $endLine);

        if (function_exists('app')) {
            $this->setLocale(app()->getLocale());
            $this->setStorage(new LaravelStorage());
        }
    }

    /**
     * @param $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function translate()
    {
        $this->loadLocaleTranslations();
        $location = $this->findSource();

        return $this->findTranslation($location['signature'] ?: $this->input, $location['file']) ?: $this->input;
    }

    /**
     * @return StorageContract
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param StorageContract $storage
     * @return $this
     */
    public function setStorage(StorageContract $storage)
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @param $text
     * @param $file
     * @return string
     */
    protected function findTranslation($text, $file)
    {
        $match = collect(static::$translations[$this->locale] ?? [])->first(function($translation) use ($text, $file) {
            return $translation['file'] == $file && trim($translation['original_version']) == trim($text);
        });

        if ($match) return $this->insertValues($this->input, $match['text'], $match['original_version']);
    }

    protected function insertValues($rendered, $newSignature, $originalSignature)
    {
        $pattern = preg_replace('/({[a-z]+\d+})/', '(.+)', $originalSignature);
        preg_match("/{$pattern}/m", $rendered, $values);
        $index = 0;

        return preg_replace_callback('/({[a-z]+\d+})/', function($matches) use ($values, &$index) {
            return $values[++$index];
        }, $newSignature, -1, $count, PREG_OFFSET_CAPTURE);
    }

    protected function loadLocaleTranslations()
    {
        $translations = $this->getStorage()->loadTranslations($this->locale);

        static::$translations[$this->locale] = empty($translations) ? $this->getStorage()->loadTranslations() : $translations;
    }
}
