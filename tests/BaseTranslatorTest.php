<?php

use PhpParser\ParserFactory;

class BaseTranslatorTest extends \PHPUnit\Framework\TestCase {
    /**
     * @test
     */
    public function signature_can_be_found_for_multiline_input()
    {
        $reader = new \Pushkin\Reader("1 pages and 0 processed entries", basename(__DIR__) . '/fixtures/partial_template.blade', 13, 15);

        $data = $reader->findSource();
        $this->assertEquals("{variable1} pages and {variable2} processed entries", $data['signature']);
    }
}