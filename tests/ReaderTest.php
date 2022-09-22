<?php

use PhpParser\ParserFactory;
use Paragraph\Paragraph;

class ReaderTest extends \PHPUnit\Framework\TestCase {
    /**
     * @test
     */
    public function direct_function_usage_can_be_parsed_as_well()
    {
        Paragraph::enableReader();
        $output = p("Test me");
        preg_match('/paragraph-begin (.+) -->.+<!--/', $output, $matches);
        $decoded = json_decode($matches[1], true);
        $this->assertEquals('Test me', $decoded['placeholder']);
        $this->assertStringContainsString('ReaderTest', $decoded['file']);
    }
}
