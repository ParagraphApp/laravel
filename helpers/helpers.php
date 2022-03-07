<?php

if (! function_exists('p')) {
    /**
     * @param string $input
     * @param string $startLine
     * @param string $endLine
     * @return string
     */
    function p($input, $startLine = null, $endLine = null)
    {
        return resolve(\Paragraph\TranslatorContract::class, compact('input', 'startLine', 'endLine'))->translate();
    }
}
