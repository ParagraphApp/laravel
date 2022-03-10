<?php

namespace Paragraph\Storage;

interface StorageContract {
    public static function loadTranslations($locale);

    public static function saveTranslations($translations, $locale);
}
