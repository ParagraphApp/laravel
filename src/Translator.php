<?php

namespace Paragraph;

use Paragraph\Exceptions\FailedParsing;
use Paragraph\Storage\LaravelStorage;
use Paragraph\Storage\StorageContract;
use Illuminate\Support\Facades\Log;

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

        try {
            $location = $this->findSource();
        } catch (FailedParsing $e) {
            Log::error("Failed parsing while trying to translate {$this->file} line {$this->startLine}");
        }

        if (empty($location)) {
            return $this->input;
        }

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
            return (empty($translation['files']) || in_array($file, $translation['files'])) && $translation['placeholder'] == trim($text);
        });

        if ($match) return $this->insertValues($this->input, $match['text'], $match['placeholder']);
    }

    protected function insertValues($rendered, $newSignature, $originalSignature)
    {
        $pattern = trim(preg_replace('/(\\\{[a-z]+\d+\\\})/', '(.+)', preg_quote($originalSignature)));
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
