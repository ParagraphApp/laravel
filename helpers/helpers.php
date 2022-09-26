<?php

if (! function_exists('p')) {
    /**
     * @param string $input
     * @param string $startLine
     * @param string $endLine
     * @param array $text
     * @return string
     */
    function p($input, $text = null)
    {
        if (! \Paragraph\Paragraph::isReaderEnabled()) {
            return $text['compiled'] ?? $input;
        }

        return resolve(\Paragraph\Reader::class, compact('input', 'text'))
            ->tag();
    }
}
