<?php

namespace Pushkin\Storage;

interface StorageContract {
    public static function loadTranslations($locale);

    public static function saveTranslations($locale, $translations);
}