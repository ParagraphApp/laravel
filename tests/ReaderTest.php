<?php

use PhpParser\ParserFactory;

class ReaderTest extends \PHPUnit\Framework\TestCase {
    /**
     * @test
     */
    public function signature_can_be_found_for_multiline_input()
    {
        $reader = new \Paragraph\Reader("1 pages and 0 processed entries", 13, 15);
        $reader->setFile(basename(__DIR__) . '/fixtures/partial_template.blade');

        $data = $reader->findSource();
        $this->assertEquals("{variable1} pages and {variable2} processed entries", trim($data['signature']));
    }

    /**
     * @test
     */
    public function signature_can_be_found_for_multiline_input2()
    {
        $reader = new \Paragraph\Reader("We do our best trying to provide a comprehensive list of interesting events in
                        London. However, Boogie Call is ran by volunteers so we could miss something.
                        If you know an interesting live concert or a party happening in London – please log in and add it! Our goal is
                        to be the number one party & concert database for London.", 144, 149);
        $reader->setFile(basename(__DIR__) . '/fixtures/partial_template2.blade');

        $data = $reader->findSource();
        $this->assertEquals("We do our best trying to provide a comprehensive list of interesting events in {variable1}. However, Boogie Call is ran by volunteers so we could miss something. If you know an interesting live concert or a party happening in {variable2} – please log in and add it! Our goal is to be the number one party & concert database for {variable3}.", trim(preg_replace('/\s\s+/', ' ', $data['signature'])));
    }

    /**
     * @test
     */
    public function direct_function_usage_can_be_parsed_as_well()
    {
        $output = p("Test me");
        preg_match('/paragraph-begin (.+) -->.+<!--/', $output, $matches);
        $decoded = json_decode($matches[1], true);
        $this->assertNull($decoded['signature']);
        $this->assertEquals('Test me', $decoded['placeholder']);
        $this->assertStringContainsString('ReaderTest', $decoded['file']);
    }

    /**
     * @test
     */
    public function direct_function_with_embedded_variable_can_be_parsed_as_well()
    {
        $name = "baby";
        $output = p("Test me {$name}");
        preg_match('/paragraph-begin (.+) -->.+<!--/', $output, $matches);
        $decoded = json_decode($matches[1], true);
        $this->assertEquals("Test me {name}", $decoded['signature']);
        $this->assertEquals('Test me baby', $decoded['placeholder']);
        $this->assertStringContainsString('ReaderTest', $decoded['file']);
    }
}
