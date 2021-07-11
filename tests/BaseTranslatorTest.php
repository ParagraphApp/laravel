<?php

use PhpParser\ParserFactory;

class BaseTranslatorTest extends \PHPUnit\Framework\TestCase {
    /**
     * @test
     */
    public function signature_can_be_found_for_multiline_input()
    {
        $input = file_get_contents(basename(__DIR__) . '/fixtures/fragment1.html');

        $reader = new \Pushkin\Reader($input);

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($input);

        $signature = $reader->findSignature($ast);
        $this->assertEquals("{count} pages and {count} processed entries", $signature);
    }
}