<?php

use PhpParser\ParserFactory;

class BaseTranslatorTest extends \PHPUnit\Framework\TestCase {
    /**
     * @test
     */
    public function signature_can_be_found_for_multiline_input()
    {
        $reader = new \Pushkin\Reader("1 pages and 0 processed entries", 13, 15);
        $reader->setFile(basename(__DIR__) . '/fixtures/partial_template.blade');

        $data = $reader->findSource();
        $this->assertEquals("{variable1} pages and {variable2} processed entries", $data['signature']);
    }

    /**
     * @test
     */
    public function direct_function_usage_can_be_parsed_as_well()
    {
        $output = p("Test me");
        preg_match('/pushkin-begin (.+) -->.+<!--/', $output, $matches);
        $decoded = json_decode($matches[1], true);
        $this->assertNull($decoded['signature']);
        $this->assertEquals('Test me', $decoded['text']);
        $this->assertStringContainsString('BaseTranslatorTest', $decoded['file']);
    }

    /**
     * @test
     */
    public function direct_function_with_embedded_variable_can_be_parsed_as_well()
    {
        $name = "baby";
        $output = p("Test me {$name}");
        preg_match('/pushkin-begin (.+) -->.+<!--/', $output, $matches);
        $decoded = json_decode($matches[1], true);
        $this->assertEquals("Test me {name}", $decoded['signature']);
        $this->assertEquals('Test me baby', $decoded['text']);
        $this->assertStringContainsString('BaseTranslatorTest', $decoded['file']);
    }
}