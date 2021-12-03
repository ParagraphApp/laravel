<?php

use PhpParser\ParserFactory;

class TranslatorTest extends \PHPUnit\Framework\TestCase {
    /**
     * @test
     */
    public function text_is_replaced_with_translation_when_necessary()
    {
        $translator = new \Pushkin\Translator("2 pages and 1 processed entries", 13, 15);
        $storage = new \Pushkin\Storage\MemoryStorage();
        $translator->setStorage($storage)->setFile(basename(__DIR__) . '/fixtures/partial_template.blade');
        $storage->saveTranslations([
            [
                'text' => '{variable1} pages but also {variable2} processed entries',
                'locale' => 'en_US',
                'file' => '/vendor/phpunit/phpunit/src/Framework/TestCase.php',
                'original_version' => '{variable1} pages and {variable2} processed entries'
            ]
        ], 'default');

        $translation = $translator->translate();
        $this->assertEquals("2 pages but also 1 processed entries", $translation);
    }
}