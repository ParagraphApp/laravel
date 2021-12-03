<?php

use PhpParser\ParserFactory;

class PerformanceTest extends \PHPUnit\Framework\TestCase {
    const GOOD_PERFORMANCE_THRESHOLD = 15; // in milliseconds

    /**
     * @test
     */
    public function replacement_is_reasonably_fast()
    {
        $translator = new \Pushkin\Translator("2 pages and 1 processed entries", 13, 15);
        $storage = new \Pushkin\Storage\LaravelStorage();
        $translator->setStorage($storage)->setFile(basename(__DIR__) . '/fixtures/partial_template.blade');

        $translations = array_map(function() {
            return [
                'text' => 'Some random text',
                'locale' => 'en_US',
                'file' => '/vendor/phpunit/phpunit/src/Framework/TestCase.php',
                'original_version' => 'Another random text'
            ];
        }, range(1, 5000));

        $translations[] = [
                'text' => '{variable1} pages but also {variable2} processed entries',
                'locale' => 'en_US',
                'file' => '/vendor/phpunit/phpunit/src/Framework/TestCase.php',
                'original_version' => '{variable1} pages and {variable2} processed entries'
            ];

        $storage->saveTranslations($translations, 'default');

        $start = microtime(true);
        $translation = $translator->translate();
        $finish = microtime(true);
        //print_r(($finish - $start) * 1000);

        $this->assertLessThan(static::GOOD_PERFORMANCE_THRESHOLD, ($finish - $start) * 1000);
        $this->assertEquals("2 pages but also 1 processed entries", $translation);
    }
}

function storage_path($path)
{
    return '/tmp/' . $path;
}